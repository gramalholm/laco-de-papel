<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;
use App\Models\MembroClubeRepository;

$usuario = Auth::exigirUsuario();

$membroRepo = new MembroClubeRepository();
$todos = $membroRepo->listarClubesDoUsuario($usuario['id_usuario']);

// Carrega todos os vínculos de uma vez e agrupa na memória entre convites, clubes geridos e participações
$convites = [];
$responsavel = [];
$membro = [];

foreach ($todos as $item) {
    if ($item['status_membro'] === 'pendente') {
        $convites[] = $item;
    } elseif ($item['papel'] === 'responsavel') {
        $responsavel[] = $item;
    } else {
        $membro[] = $item;
    }
}

View::render('meus-clubes', [
    'pageTitle' => 'Meus clubes — Laço de Papel',
    'activeNav' => 'meus-clubes',
    'useBands' => true,
    'usuario' => $usuario,
    'convites' => $convites,
    'responsavel' => $responsavel,
    'membro' => $membro,
]);
