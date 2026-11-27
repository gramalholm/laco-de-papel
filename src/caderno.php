<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\View;
use App\Models\AnotacaoRepository;
use App\Models\CicloRepository;
use App\Models\ClubeRepository;
use App\Models\EtapaRepository;
use App\Models\MembroClubeRepository;
use App\Models\ProgressoRepository;

$idCiclo = (int) ($_GET['id'] ?? 0);
if ($idCiclo <= 0) {
    View::renderError(404, 'Caderno não encontrado', 'O ciclo informado não foi localizado.');
}

$cicloRepo = new CicloRepository();
$ciclo = $cicloRepo->buscarPorId($idCiclo);
if ($ciclo === null) {
    View::renderError(404, 'Caderno não encontrado', 'O ciclo informado não foi localizado.');
}

$idClube = (int) $ciclo['id_clube'];
$usuario = Auth::exigirUsuario();

// Em clube privado sem vínculo ativo, responde 404 neutro para não revelar que o ciclo existe
$vinculo = Authorization::obterVinculo($idClube, $usuario['id_usuario']);
if ($vinculo === null || $vinculo['status'] !== 'ativo') {
    if (($ciclo['clube_visibilidade'] ?? '') === 'privado') {
        View::renderError(404, 'Caderno não encontrado', 'O conteúdo solicitado não foi localizado ou você não tem permissão para visualizá-lo.');
    }
    View::renderError(403, 'Acesso restrito ao clube', 'Você precisa participar deste clube para abrir o Caderno de Margens.');
}

$tipoFiltro = trim((string) ($_GET['tipo'] ?? ''));
$tiposValidos = ['grifo', 'duvida', 'teoria', 'reflexao', 'comentario'];
if (!in_array($tipoFiltro, $tiposValidos, true)) {
    $tipoFiltro = '';
}
$etapaFiltro = (int) ($_GET['etapa'] ?? 0);

$clubeRepo = new ClubeRepository();
$membroRepo = new MembroClubeRepository();
$progressoRepo = new ProgressoRepository();
$etapaRepo = new EtapaRepository();
$anotacaoRepo = new AnotacaoRepository();

$clube = $clubeRepo->buscarPorId($idClube);
$totalMembros = $membroRepo->contarMembrosAtivos($idClube);
$meuProgressoPct = $progressoRepo->obterPercentualUsuario($idCiclo, $usuario['id_usuario']);
$etapas = $etapaRepo->listarPorCiclo($idCiclo);

// Consulta anti-spoiler no SQL: textos com posicao_pct > meuProgressoPct chegam como NULL
$anotacoes = $anotacaoRepo->listarDoCicloParaLeitor(
    $idCiclo,
    $usuario['id_usuario'],
    $meuProgressoPct,
    $tipoFiltro !== '' ? $tipoFiltro : null,
    $etapaFiltro > 0 ? $etapaFiltro : null
);

// Mantém o formulário de nova anotação aberto se veio de ?nova=1 ou voltou com erro de validação
$abrirNova = (
    ($_GET['nova'] ?? '') === '1'
    || old('conteudo') !== ''
    || field_error('conteudo') !== null
    || field_error('posicao_pct') !== null
);

View::render('caderno', [
    'pageTitle' => 'Caderno de Margens — ' . ($ciclo['livro_titulo'] ?: $clube['nome']),
    'activeNav' => 'meus-clubes',
    'useBands' => true,
    'scripts' => ['assets/js/annotations.js'],
    'usuario' => $usuario,
    'clube' => $clube,
    'totalMembros' => $totalMembros,
    'ciclo' => $ciclo,
    'meuProgressoPct' => $meuProgressoPct,
    'etapas' => $etapas,
    'anotacoes' => $anotacoes,
    'tipoFiltro' => $tipoFiltro,
    'etapaFiltro' => $etapaFiltro,
    'abrirNova' => $abrirNova,
]);
