<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\AnotacaoRepository;
use App\Models\CicloRepository;
use App\Models\EdicaoRepository;
use App\Models\EtapaRepository;
use App\Models\IndicacaoRepository;
use App\Models\LivroRepository;
use App\Services\ClubeService;

exigirPost('excluir.php');
Csrf::exigirValido('excluir.php');

$usuario = Auth::exigirUsuario();
$idUsuario = $usuario['id_usuario'];

$tipo = trim((string) ($_POST['tipo'] ?? ''));
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || $tipo === '') {
    Flash::erro('Registro inválido para exclusão.');
    redirect('excluir.php');
}

// Centraliza as exclusões confirmadas por POST, revalidando autoria, papel e dependências no servidor
try {
    // Clube nunca sofre DELETE físico; segue sempre para arquivamento
    if ($tipo === 'clube') {
        (new ClubeService())->arquivarClube($id);
        Flash::info('Clube arquivado. O histórico de leituras foi preservado.');
        redirect('meus-clubes.php');
    }

    if ($tipo === 'anotacao') {
        $repo = new AnotacaoRepository();
        $an = $repo->buscarPorId($id);
        if ($an === null) {
            throw new DomainException('Anotação não encontrada.');
        }
        if ((int) $an['id_usuario'] !== $idUsuario) {
            throw new DomainException('Você só pode excluir suas próprias anotações.');
        }
        Authorization::exigirMembroAtivo((int) $an['id_clube']);
        if (($an['clube_status'] ?? '') !== 'ativo' || ($an['ciclo_status'] ?? '') !== 'em_andamento') {
            throw new DomainException('Anotações de ciclos encerrados ou clubes arquivados ficam preservadas no histórico.');
        }
        $idCiclo = (int) $an['id_ciclo'];
        $repo->excluir($id);
        Flash::sucesso('Anotação removida do Caderno de Margens.');
        redirect('caderno.php?id=' . $idCiclo);
    }

    if ($tipo === 'livro') {
        $repo = new LivroRepository();
        $lv = $repo->buscarPorId($id);
        if ($lv === null) {
            throw new DomainException('Livro não encontrado.');
        }
        if ((int) ($lv['criado_por'] ?? 0) !== $idUsuario) {
            throw new DomainException('Apenas quem cadastrou este livro pode excluí-lo.');
        }
        $deps = $repo->contarDependencias($id);
        if ($deps['ciclos'] > 0 || $deps['indicacoes'] > 0 || $deps['edicoes'] > 0) {
            throw new DomainException('Este livro possui vínculos ativos (edições, indicações ou ciclos) e não pode ser excluído.');
        }
        $repo->excluir($id);
        Flash::sucesso('Livro removido do acervo.');
        redirect('acervo.php');
    }

    if ($tipo === 'edicao') {
        $repo = new EdicaoRepository();
        $ed = $repo->buscarPorId($id);
        if ($ed === null) {
            throw new DomainException('Edição não encontrada.');
        }
        if ((int) ($ed['criado_por'] ?? 0) !== $idUsuario) {
            throw new DomainException('Apenas quem cadastrou esta edição pode excluí-la.');
        }
        if ($repo->contarDependencias($id) > 0) {
            throw new DomainException('Esta edição está vinculada ao progresso de leitores e não pode ser excluída.');
        }
        $idLivro = (int) $ed['id_livro'];
        $repo->excluir($id);
        Flash::sucesso('Edição física removida.');
        redirect('livro.php?id=' . $idLivro);
    }

    if ($tipo === 'etapa') {
        $repo = new EtapaRepository();
        $et = $repo->buscarPorId($id);
        if ($et === null) {
            throw new DomainException('Etapa não encontrada.');
        }
        Authorization::exigirResponsavel((int) $et['id_clube']);
        if (($et['clube_status'] ?? '') !== 'ativo' || in_array($et['ciclo_status'] ?? '', ['encerrado', 'cancelado'], true)) {
            throw new DomainException('Etapas de ciclos encerrados ou clubes arquivados não podem ser removidas.');
        }
        $idCiclo = (int) $et['id_ciclo'];
        $repo->excluir($id);
        Flash::sucesso('Etapa removida do ciclo.');
        redirect('ciclo.php?id=' . $idCiclo . '#etapas');
    }

    if ($tipo === 'indicacao') {
        $repo = new IndicacaoRepository();
        $ind = $repo->buscarPorId($id);
        if ($ind === null) {
            throw new DomainException('Indicação não encontrada.');
        }
        $vinculo = Authorization::exigirMembroAtivo((int) $ind['id_clube']);
        if ((int) $ind['id_usuario'] !== $idUsuario && $vinculo['papel'] !== 'responsavel') {
            throw new DomainException('Sem permissão para remover esta indicação.');
        }
        // Só permite retirar indicação antes do fim da votação para não alterar um resultado já fechado
        $ciclo = (new CicloRepository())->buscarPorId((int) $ind['id_ciclo']);
        if ($ciclo === null || $ciclo['status'] !== 'votacao') {
            throw new DomainException('Indicações só podem ser removidas enquanto a votação estiver aberta.');
        }
        $idCiclo = (int) $ind['id_ciclo'];
        $repo->excluir($id);
        Flash::sucesso('Indicação removida da votação.');
        redirect('ciclo.php?id=' . $idCiclo);
    }

    throw new DomainException('Tipo de exclusão não reconhecido.');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect('excluir.php');
}
