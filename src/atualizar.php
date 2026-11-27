<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\View;
use App\Models\AnotacaoRepository;
use App\Models\CicloRepository;
use App\Models\EdicaoRepository;
use App\Models\EtapaRepository;
use App\Models\LivroRepository;
use App\Models\MembroClubeRepository;
use App\Models\ProgressoRepository;

$usuario = Auth::exigirUsuario();
$idUsuario = $usuario['id_usuario'];

$tipo = trim((string) ($_GET['tipo'] ?? ''));
$id = (int) ($_GET['id'] ?? 0);

// Edição de clube reutiliza diretamente o formulário dedicado clube-form.php
if ($tipo === 'clube' && $id > 0) {
    redirect('clube-form.php?id=' . $id);
}

$registro = null;
$extras = [];
$tituloPagina = 'Atualizar registros';

if ($tipo !== '' && $id > 0) {
    if ($tipo === 'livro') {
        $livroRepo = new LivroRepository();
        $registro = $livroRepo->buscarPorId($id);
        if ($registro === null) {
            View::renderError(404, 'Livro não encontrado', 'A obra informada não existe.');
        }
        if ((int) ($registro['criado_por'] ?? 0) !== $idUsuario) {
            View::renderError(403, 'Sem permissão', 'Apenas quem cadastrou este livro pode editar seus dados.');
        }
        $tituloPagina = 'Editar livro';
    } elseif ($tipo === 'edicao') {
        $edicaoRepo = new EdicaoRepository();
        $registro = $edicaoRepo->buscarPorId($id);
        if ($registro === null) {
            View::renderError(404, 'Edição não encontrada', 'A edição informada não existe.');
        }
        if ((int) ($registro['criado_por'] ?? 0) !== $idUsuario) {
            View::renderError(403, 'Sem permissão', 'Apenas quem cadastrou esta edição pode editá-la.');
        }
        $tituloPagina = 'Editar edição física';
    } elseif ($tipo === 'anotacao') {
        $anotacaoRepo = new AnotacaoRepository();
        $registro = $anotacaoRepo->buscarPorId($id);
        if ($registro === null) {
            View::renderError(404, 'Anotação não encontrada', 'A anotação informada não existe.');
        }
        $vinculo = Authorization::obterVinculo((int) $registro['id_clube'], $idUsuario);
        if (($registro['clube_visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
            View::renderError(404, 'Anotação não encontrada', 'A anotação informada não existe.');
        }
        Authorization::exigirMembroAtivo((int) $registro['id_clube']);
        if ((int) $registro['id_usuario'] !== $idUsuario) {
            View::renderError(403, 'Sem permissão', 'Você só pode editar suas próprias anotações.');
        }
        $idCiclo = (int) $registro['id_ciclo'];
        $cicloRepo = new CicloRepository();
        $ciclo = $cicloRepo->buscarPorId($idCiclo);
        // Anotações ficam congeladas assim que o ciclo sai de andamento ou o clube é arquivado
        if ($ciclo === null || $ciclo['status'] !== 'em_andamento' || ($ciclo['clube_status'] ?? '') !== 'ativo') {
            View::renderError(403, 'Ciclo encerrado', 'Anotações de ciclos encerrados ou clubes arquivados não podem mais ser editadas.');
        }

        $progressoRepo = new ProgressoRepository();
        $etapaRepo = new EtapaRepository();
        $extras['meuProgressoPct'] = $progressoRepo->obterPercentualUsuario($idCiclo, $idUsuario);
        $extras['etapas'] = $etapaRepo->listarPorCiclo($idCiclo);
        $tituloPagina = 'Editar anotação';
    } elseif ($tipo === 'etapa') {
        $etapaRepo = new EtapaRepository();
        $registro = $etapaRepo->buscarPorId($id);
        if ($registro === null) {
            View::renderError(404, 'Etapa não encontrada', 'A etapa informada não existe.');
        }
        $vinculo = Authorization::obterVinculo((int) $registro['id_clube'], $idUsuario);
        if (($registro['clube_visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
            View::renderError(404, 'Etapa não encontrada', 'A etapa informada não existe.');
        }
        Authorization::exigirResponsavel((int) $registro['id_clube']);
        if (($registro['clube_status'] ?? '') !== 'ativo' || in_array($registro['ciclo_status'] ?? '', ['encerrado', 'cancelado'], true)) {
            View::renderError(403, 'Ciclo encerrado', 'Etapas de ciclos encerrados ou clubes arquivados não podem ser editadas.');
        }
        $tituloPagina = 'Editar etapa de leitura';
    } else {
        View::renderError(404, 'Tipo de registro inválido', 'O tipo de atualização informado não é reconhecido.');
    }
}

// Sem parâmetros: exibe índice organizado dos registros editáveis do usuário
$indice = [
    'clubes' => [],
    'anotacoes' => [],
    'livros' => [],
    'edicoes' => [],
];

if ($registro === null) {
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

View::render('atualizar', [
    'pageTitle' => $tituloPagina . ' — Laço de Papel',
    'activeNav' => '',
    'useBands' => true,
    'scripts' => ['assets/js/catalog.js', 'assets/js/annotations.js'],
    'usuario' => $usuario,
    'tipo' => $registro !== null ? $tipo : '',
    'registro' => $registro,
    'extras' => $extras,
    'indice' => $indice,
    'tituloPagina' => $tituloPagina,
]);
