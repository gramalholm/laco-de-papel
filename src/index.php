<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;
use App\Models\ClubeRepository;
use App\Models\LivroRepository;
use App\Models\MembroClubeRepository;
use App\Models\TemaClubeRepository;

$usuario = Auth::user();
$temaRepo = new TemaClubeRepository();
$clubeRepo = new ClubeRepository();
$livroRepo = new LivroRepository();

$temas = $temaRepo->listarAtivos();
$clubesPublicos = $clubeRepo->buscarDestaquesPublicos(4);
$livrosAcervo = $livroRepo->listarRecentes(4);

// Escolhe o primeiro clube público com leitura em andamento para o destaque da capa
$leituraEmDestaque = null;
foreach ($clubesPublicos as $c) {
    if (!empty($c['ciclo_ativo_livro'])) {
        $leituraEmDestaque = $c;
        break;
    }
}

$meusClubesRecentes = [];
$convitesPendentes = [];

// Para quem está logado, separa convites pendentes e os clubes ativos recentes para o resumo inicial
if ($usuario !== null) {
    $membroRepo = new MembroClubeRepository();
    $todosVinculos = $membroRepo->listarClubesDoUsuario($usuario['id_usuario']);

    foreach ($todosVinculos as $item) {
        if ($item['status_membro'] === 'pendente') {
            $convitesPendentes[] = $item;
        } elseif ($item['status_membro'] === 'ativo' && count($meusClubesRecentes) < 4) {
            $meusClubesRecentes[] = $item;
        }
    }
}

View::render('home', [
    'pageTitle' => 'Laço de Papel — Histórias aproximam. Leituras criam laços.',
    'activeNav' => 'inicio',
    'useBands' => true,
    'usuario' => $usuario,
    'temas' => $temas,
    'clubesPublicos' => $clubesPublicos,
    'livrosAcervo' => $livrosAcervo,
    'leituraEmDestaque' => $leituraEmDestaque,
    'meusClubesRecentes' => $meusClubesRecentes,
    'convitesPendentes' => $convitesPendentes,
]);
