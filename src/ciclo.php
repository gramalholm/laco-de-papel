<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\View;
use App\Models\CicloRepository;
use App\Models\ClubeRepository;
use App\Models\EdicaoRepository;
use App\Models\EtapaRepository;
use App\Models\IndicacaoRepository;
use App\Models\LivroRepository;
use App\Models\MembroClubeRepository;
use App\Models\ProgressoRepository;
use App\Models\VotoRepository;

$idCiclo = (int) ($_GET['id'] ?? 0);
if ($idCiclo <= 0) {
    View::renderError(404, 'Ciclo não encontrado', 'O ciclo de leitura informado não foi localizado.');
}

$cicloRepo = new CicloRepository();
$ciclo = $cicloRepo->buscarPorId($idCiclo);
if ($ciclo === null) {
    View::renderError(404, 'Ciclo não encontrado', 'O ciclo de leitura informado não foi localizado.');
}

$idClube = (int) $ciclo['id_clube'];
$usuario = Auth::exigirUsuario();

// Se o clube for privado e o usuário não for membro ativo, responder 404 neutro sem vazar existência
$vinculo = Authorization::obterVinculo($idClube, $usuario['id_usuario']);
if ($vinculo === null || $vinculo['status'] !== 'ativo') {
    if (($ciclo['clube_visibilidade'] ?? '') === 'privado') {
        View::renderError(404, 'Ciclo não encontrado', 'O conteúdo solicitado não foi localizado ou você não tem permissão para visualizá-lo.');
    }
    View::renderError(403, 'Acesso restrito ao clube', 'Entre neste clube para acompanhar o ciclo de leitura e participar das votações.');
}

$ehResponsavel = ($vinculo['papel'] === 'responsavel');

$clubeRepo = new ClubeRepository();
$membroRepo = new MembroClubeRepository();
$clube = $clubeRepo->buscarPorId($idClube);
$totalMembros = $membroRepo->contarMembrosAtivos($idClube);

$indicacoes = [];
$meuVotoId = null;
$livrosAcervo = [];
$edicoesLivro = [];
$meuProgresso = null;
$resumoColetivo = ['total_leitores' => 0, 'media_pct' => 0.0, 'faixas' => ['0-25' => 0, '26-50' => 0, '51-75' => 0, '76-100' => 0]];
$etapas = [];
$capaLivro = (string) ($ciclo['livro_capa_url'] ?? '');

// Consulta só o que pertence ao estado atual do ciclo: votação ou acompanhamento da leitura
if ($ciclo['status'] === 'votacao') {
    $indicacaoRepo = new IndicacaoRepository();
    $votoRepo = new VotoRepository();
    $livroRepo = new LivroRepository();

    $indicacoes = $indicacaoRepo->listarComVotos($idCiclo);
    $meuVotoId = $votoRepo->buscarIndicacaoVotada($idCiclo, $usuario['id_usuario']);
    $livrosAcervo = $livroRepo->listarParaSelect();
} else {
    $progressoRepo = new ProgressoRepository();
    $etapaRepo = new EtapaRepository();

    $meuProgresso = $progressoRepo->buscarDoUsuarioNoCiclo($idCiclo, $usuario['id_usuario']);
    $resumoColetivo = $progressoRepo->resumoColetivoDoCiclo($idCiclo);
    $etapas = $etapaRepo->listarPorCiclo($idCiclo);

    if (!empty($ciclo['id_livro_escolhido'])) {
        $edicaoRepo = new EdicaoRepository();
        $edicoesLivro = $edicaoRepo->listarPorLivro((int) $ciclo['id_livro_escolhido']);
        if ($capaLivro === '') {
            foreach ($edicoesLivro as $ed) {
                if (!empty($ed['capa_url'])) {
                    $capaLivro = (string) $ed['capa_url'];
                    break;
                }
            }
        }
    }
}

View::render('ciclo', [
    'pageTitle' => ($ciclo['livro_titulo'] ?: ($ciclo['titulo_ciclo'] ?: 'Sala do ciclo')) . ' — Laço de Papel',
    'activeNav' => 'meus-clubes',
    'useBands' => true,
    'scripts' => ['assets/js/progress.js?v=2'],
    'usuario' => $usuario,
    'clube' => $clube,
    'totalMembros' => $totalMembros,
    'ciclo' => $ciclo,
    'ehResponsavel' => $ehResponsavel,
    'indicacoes' => $indicacoes,
    'meuVotoId' => $meuVotoId,
    'livrosAcervo' => $livrosAcervo,
    'edicoesLivro' => $edicoesLivro,
    'meuProgresso' => $meuProgresso,
    'resumoColetivo' => $resumoColetivo,
    'etapas' => $etapas,
    'capaLivro' => $capaLivro,
]);
