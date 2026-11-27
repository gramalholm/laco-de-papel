<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;
use App\Models\ProgressoRepository;

$usuario = Auth::exigirUsuario();

$statusFiltro = trim((string) ($_GET['status'] ?? ''));
if (!in_array($statusFiltro, ['lendo', 'concluido', 'pausado'], true)) {
    $statusFiltro = '';
}

// Busca todas as leituras de uma vez para manter os contadores das abas corretos ao filtrar por status
$progressoRepo = new ProgressoRepository();
$todasLeituras = $progressoRepo->listarEstanteDoUsuario($usuario['id_usuario'], null);

$contagens = [
    'todas' => count($todasLeituras),
    'lendo' => 0,
    'concluido' => 0,
    'pausado' => 0,
];

$leituras = [];
foreach ($todasLeituras as $item) {
    $st = (string) ($item['status_leitura'] ?? 'lendo');
    if (isset($contagens[$st])) {
        $contagens[$st]++;
    }
    if ($statusFiltro === '' || $st === $statusFiltro) {
        $leituras[] = $item;
    }
}

View::render('minha-estante', [
    'pageTitle' => 'Minha estante — Laço de Papel',
    'activeNav' => 'estante',
    'useBands' => true,
    'usuario' => $usuario,
    'statusFiltro' => $statusFiltro,
    'contagens' => $contagens,
    'leituras' => $leituras,
]);
