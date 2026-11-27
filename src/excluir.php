<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\View;
use App\Models\AnotacaoRepository;
use App\Models\ClubeRepository;
use App\Models\EdicaoRepository;
use App\Models\EtapaRepository;
use App\Models\IndicacaoRepository;
use App\Models\LivroRepository;
use App\Models\MembroClubeRepository;

$usuario = Auth::exigirUsuario();
$idUsuario = $usuario['id_usuario'];

$tipo = trim((string) ($_GET['tipo'] ?? ''));
$id = (int) ($_GET['id'] ?? 0);

// Esta página apenas valida permissão e monta a confirmação; nenhuma exclusão acontece via GET
$alvo = null;

if ($tipo !== '' && $id > 0) {
    if ($tipo === 'anotacao') {
        $an = (new AnotacaoRepository())->buscarPorId($id);
        if ($an === null) {
            View::renderError(404, 'Anotação não encontrada', 'O registro informado não existe.');
        }
        $vinculo = Authorization::obterVinculo((int) $an['id_clube'], $idUsuario);
        if (($an['clube_visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
            View::renderError(404, 'Anotação não encontrada', 'O registro informado não existe.');
        }
        Authorization::exigirMembroAtivo((int) $an['id_clube']);
        if ((int) $an['id_usuario'] !== $idUsuario) {
            View::renderError(403, 'Sem permissão', 'Você só pode excluir suas próprias anotações.');
        }
        $bloqueio = (($an['clube_status'] ?? '') !== 'ativo' || ($an['ciclo_status'] ?? '') !== 'em_andamento')
            ? 'Anotações de ciclos encerrados ou clubes arquivados ficam preservadas no histórico.'
            : null;
        $alvo = [
            'tipo' => 'anotacao',
            'id' => $id,
            'nome' => (string) ($an['titulo'] ?: 'Anotação em ' . formatarPercentual((float) $an['posicao_pct'])),
            'contexto' => 'Clube ' . $an['clube_nome'],
            'consequencia' => 'Esta anotação será removida permanentemente do Caderno de Margens.',
            'bloqueio' => $bloqueio,
            'rotuloBotao' => 'Excluir anotação',
            'urlVoltar' => 'caderno.php?id=' . (int) $an['id_ciclo'],
        ];
    } elseif ($tipo === 'livro') {
        $livroRepo = new LivroRepository();
        $lv = $livroRepo->buscarPorId($id);
        if ($lv === null) {
            View::renderError(404, 'Livro não encontrado', 'A obra informada não existe.');
        }
        if ((int) ($lv['criado_por'] ?? 0) !== $idUsuario) {
            View::renderError(403, 'Sem permissão', 'Apenas quem cadastrou este livro pode excluí-lo.');
        }
        // Livros já ligados a ciclos, indicações ou edições ficam bloqueados para evitar órfãos no histórico
        $deps = $livroRepo->contarDependencias($id);
        $bloqueio = null;
        if ($deps['ciclos'] > 0 || $deps['indicacoes'] > 0 || $deps['edicoes'] > 0) {
            $bloqueio = 'Este livro não pode ser excluído porque já está vinculado a '
                . $deps['ciclos'] . ' ciclo(s), '
                . $deps['indicacoes'] . ' indicação(ões) e '
                . $deps['edicoes'] . ' edição(ões) física(s).';
        }
        $alvo = [
            'tipo' => 'livro',
            'id' => $id,
            'nome' => (string) $lv['titulo'] . ' — ' . (string) $lv['autor_principal'],
            'contexto' => 'Obra do acervo compartilhado',
            'consequencia' => 'O registro será removido definitivamente do acervo.',
            'bloqueio' => $bloqueio,
            'rotuloBotao' => 'Excluir livro',
            'urlVoltar' => 'livro.php?id=' . $id,
        ];
    } elseif ($tipo === 'edicao') {
        $edRepo = new EdicaoRepository();
        $ed = $edRepo->buscarPorId($id);
        if ($ed === null) {
            View::renderError(404, 'Edição não encontrada', 'A edição informada não existe.');
        }
        if ((int) ($ed['criado_por'] ?? 0) !== $idUsuario) {
            View::renderError(403, 'Sem permissão', 'Apenas quem cadastrou esta edição pode excluí-la.');
        }
        $emUso = $edRepo->contarDependencias($id);
        $bloqueio = $emUso > 0
            ? 'Esta edição não pode ser excluída porque está sendo usada no progresso de ' . $emUso . ' leitura(s).'
            : null;
        $alvo = [
            'tipo' => 'edicao',
            'id' => $id,
            'nome' => (string) ($ed['titulo_edicao'] ?: ($ed['editora'] ?: 'Edição física #' . $id)),
            'contexto' => 'Obra: ' . $ed['livro_titulo'],
            'consequencia' => 'Esta edição será removida da ficha do livro.',
            'bloqueio' => $bloqueio,
            'rotuloBotao' => 'Excluir edição',
            'urlVoltar' => 'livro.php?id=' . (int) $ed['id_livro'],
        ];
    } elseif ($tipo === 'etapa') {
        $et = (new EtapaRepository())->buscarPorId($id);
        if ($et === null) {
            View::renderError(404, 'Etapa não encontrada', 'A etapa informada não existe.');
        }
        $vinculo = Authorization::obterVinculo((int) $et['id_clube'], $idUsuario);
        if (($et['clube_visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
            View::renderError(404, 'Etapa não encontrada', 'A etapa informada não existe.');
        }
        Authorization::exigirResponsavel((int) $et['id_clube']);
        $bloqueio = (($et['clube_status'] ?? '') !== 'ativo' || in_array($et['ciclo_status'] ?? '', ['encerrado', 'cancelado'], true))
            ? 'Etapas de ciclos encerrados ou clubes arquivados não podem ser removidas.'
            : null;
        $alvo = [
            'tipo' => 'etapa',
            'id' => $id,
            'nome' => 'Etapa ' . (int) $et['ordem'] . ' — ' . (string) $et['titulo'],
            'contexto' => 'Clube ' . $et['clube_nome'],
            'consequencia' => 'A etapa será removida; as anotações existentes serão mantidas no Caderno sem vínculo de etapa.',
            'bloqueio' => $bloqueio,
            'rotuloBotao' => 'Excluir etapa',
            'urlVoltar' => 'ciclo.php?id=' . (int) $et['id_ciclo'] . '#etapas',
        ];
    } elseif ($tipo === 'indicacao') {
        $ind = (new IndicacaoRepository())->buscarPorId($id);
        if ($ind === null) {
            View::renderError(404, 'Indicação não encontrada', 'A indicação informada não existe.');
        }
        $vinculo = Authorization::obterVinculo((int) $ind['id_clube'], $idUsuario);
        if (($ind['clube_visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
            View::renderError(404, 'Indicação não encontrada', 'A indicação informada não existe.');
        }
        $vinculo = Authorization::exigirMembroAtivo((int) $ind['id_clube']);
        if ((int) $ind['id_usuario'] !== $idUsuario && $vinculo['papel'] !== 'responsavel') {
            View::renderError(403, 'Sem permissão', 'Apenas o autor da indicação ou o responsável do clube pode removê-la.');
        }
        $bloqueio = (($ind['clube_status'] ?? '') !== 'ativo' || ($ind['ciclo_status'] ?? '') !== 'votacao')
            ? 'Indicações só podem ser removidas enquanto a votação estiver aberta.'
            : null;
        $alvo = [
            'tipo' => 'indicacao',
            'id' => $id,
            'nome' => (string) $ind['livro_titulo'],
            'contexto' => 'Indicação em votação',
            'consequencia' => 'A indicação e eventuais votos recebidos por ela serão removidos desta votação.',
            'bloqueio' => $bloqueio,
            'rotuloBotao' => 'Remover indicação',
            'urlVoltar' => 'ciclo.php?id=' . (int) $ind['id_ciclo'],
        ];
    } elseif ($tipo === 'clube') {
        $clube = (new ClubeRepository())->buscarPorId($id);
        if ($clube === null) {
            View::renderError(404, 'Clube não encontrado', 'O clube informado não existe.');
        }
        $vinculo = Authorization::obterVinculo($id, $idUsuario);
        if (($clube['visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
            View::renderError(404, 'Clube não encontrado', 'O clube informado não existe.');
        }
        Authorization::exigirResponsavel($id);
        // Clubes nunca são apagados fisicamente: a confirmação aqui prepara apenas o arquivamento
        $alvo = [
            'tipo' => 'clube',
            'id' => $id,
            'nome' => (string) $clube['nome'],
            'contexto' => 'Arquivamento de clube',
            'consequencia' => 'Clubes não são apagados definitivamente para preservar a memória de ciclos e anotações. Ao arquivar, novas entradas e edições ficam encerradas.',
            'bloqueio' => $clube['status'] === 'arquivado' ? 'Este clube já está arquivado.' : null,
            'rotuloBotao' => 'Confirmar arquivamento',
            'urlVoltar' => 'clube.php?id=' . $id,
        ];
    } else {
        View::renderError(404, 'Tipo inválido', 'O tipo de registro informado não é reconhecido.');
    }
}

$indice = [
    'clubes' => [],
    'anotacoes' => [],
    'livros' => [],
    'edicoes' => [],
];

if ($alvo === null) {
    $membroRepo = new MembroClubeRepository();
    foreach ($membroRepo->listarClubesDoUsuario($idUsuario) as $c) {
        if ($c['status_membro'] === 'ativo' && $c['papel'] === 'responsavel' && $c['status_clube'] === 'ativo') {
            $indice['clubes'][] = $c;
        }
    }
    $indice['anotacoes'] = (new AnotacaoRepository())->listarPorAutor($idUsuario, true);
    $indice['livros'] = (new LivroRepository())->listarPorCriador($idUsuario);
    $indice['edicoes'] = (new EdicaoRepository())->listarPorCriador($idUsuario);
}

View::render('excluir', [
    'pageTitle' => ($alvo !== null ? 'Confirmar ação' : 'Excluir ou arquivar') . ' — Laço de Papel',
    'activeNav' => '',
    'useBands' => true,
    'alvo' => $alvo,
    'indice' => $indice,
]);
