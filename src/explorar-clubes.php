<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\View;
use App\Models\ClubeRepository;
use App\Models\TemaClubeRepository;

$termo = trim((string) ($_GET['q'] ?? ''));
$idTema = (int) ($_GET['tema'] ?? 0);

$temaRepo = new TemaClubeRepository();
$clubeRepo = new ClubeRepository();

$temas = $temaRepo->listarAtivos();
// Só aplica o filtro de tema se o ID informado realmente existir entre os temas ativos
$temaSelecionado = $idTema > 0 ? $temaRepo->buscarPorId($idTema) : null;
$clubes = $clubeRepo->buscarPublicos(
    $termo !== '' ? $termo : null,
    $temaSelecionado !== null ? $idTema : null,
    30
);

View::render('explorar-clubes', [
    'pageTitle' => 'Explorar clubes — Laço de Papel',
    'activeNav' => 'explorar',
    'useBands' => true,
    'scripts' => ['assets/js/clubs.js'],
    'temas' => $temas,
    'clubes' => $clubes,
    'termo' => $termo,
    'idTema' => $temaSelecionado !== null ? $idTema : 0,
    'temaSelecionado' => $temaSelecionado,
]);
