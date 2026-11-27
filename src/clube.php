<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\View;
use App\Models\CicloRepository;
use App\Models\ClubeRepository;
use App\Models\LivroRepository;
use App\Models\MembroClubeRepository;

$idClube = (int) ($_GET['id'] ?? 0);
if ($idClube <= 0) {
    View::renderError(404, 'Clube não encontrado', 'O clube solicitado não foi localizado.');
}

$clubeRepo = new ClubeRepository();
$clube = $clubeRepo->buscarPorId($idClube);
if ($clube === null) {
    View::renderError(404, 'Clube não encontrado', 'O clube solicitado não foi localizado.');
}

$usuario = Auth::user();
$acesso = Authorization::resolverAcessoClube($clube, $usuario !== null ? $usuario['id_usuario'] : null);

// Clube privado sem vínculo ativo nem convite pendente retorna 404 neutro sem vazar existência
if (!$acesso['permitido']) {
    View::renderError(404, 'Clube não encontrado', 'O conteúdo solicitado não foi localizado ou você não tem permissão para visualizá-lo.');
}

$nivel = $acesso['nivel'];
$ehMembroAtivo = in_array($nivel, ['responsavel', 'membro'], true);
$ehResponsavel = ($nivel === 'responsavel');

$membroRepo = new MembroClubeRepository();
$cicloRepo = new CicloRepository();

$totalMembros = $membroRepo->contarMembrosAtivos($idClube);
$cicloAtual = $cicloRepo->buscarAtivoDoClube($idClube);

// Dados internos só são consultados para membros ativos
$membrosAtivos = [];
$convitesPendentes = [];
$historicoCiclos = [];
$livrosDisponiveis = [];
$podeSair = false;

if ($ehMembroAtivo) {
    $membrosAtivos = $membroRepo->listarMembrosAtivos($idClube);
    $todosCiclos = $cicloRepo->listarPorClube($idClube);
    foreach ($todosCiclos as $c) {
        if (in_array($c['status'], ['encerrado', 'cancelado'], true)) {
            $historicoCiclos[] = $c;
        }
    }

    if ($ehResponsavel) {
        $convitesPendentes = $membroRepo->listarConvitesPendentesDoClube($idClube);
        // O último responsável ativo não pode sair do clube sem antes arquivá-lo
        $podeSair = $membroRepo->contarResponsaveisAtivos($idClube) > 1;
        if ($cicloAtual === null && $clube['status'] === 'ativo') {
            $livroRepo = new LivroRepository();
            $livrosDisponiveis = $livroRepo->listarParaSelect();
        }
    } else {
        $podeSair = true;
    }
}

View::render('clube', [
    'pageTitle' => $clube['nome'] . ' — Laço de Papel',
    'activeNav' => $ehMembroAtivo ? 'meus-clubes' : 'explorar',
    'useBands' => true,
    'scripts' => ['assets/js/clubs.js'],
    'usuario' => $usuario,
    'clube' => $clube,
    'nivel' => $nivel,
    'ehMembroAtivo' => $ehMembroAtivo,
    'ehResponsavel' => $ehResponsavel,
    'totalMembros' => $totalMembros,
    'cicloAtual' => $cicloAtual,
    'membrosAtivos' => $membrosAtivos,
    'convitesPendentes' => $convitesPendentes,
    'historicoCiclos' => $historicoCiclos,
    'livrosDisponiveis' => $livrosDisponiveis,
    'podeSair' => $podeSair,
]);
