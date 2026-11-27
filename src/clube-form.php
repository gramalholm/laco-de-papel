<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Flash;
use App\Core\View;
use App\Models\ClubeRepository;
use App\Models\TemaClubeRepository;

$usuario = Auth::exigirUsuario();

$idClube = (int) ($_GET['id'] ?? 0);
$clube = null;
$modoEdicao = false;

// O mesmo formulário atende criação e edição; quando há ID, valida permissão e bloqueia clubes arquivados
if ($idClube > 0) {
    $clubeRepo = new ClubeRepository();
    $clube = $clubeRepo->buscarPorId($idClube);

    if ($clube === null) {
        View::renderError(404, 'Clube não encontrado', 'O clube informado não existe.');
    }

    $vinculo = Authorization::obterVinculo($idClube, $usuario['id_usuario']);
    if (($clube['visibilidade'] ?? '') === 'privado' && ($vinculo === null || $vinculo['status'] !== 'ativo')) {
        View::renderError(404, 'Clube não encontrado', 'O clube informado não existe.');
    }

    Authorization::exigirResponsavel($idClube);

    if ($clube['status'] === 'arquivado') {
        Flash::aviso('Clubes arquivados não podem ser editados.');
        redirect('clube.php?id=' . $idClube);
    }

    $modoEdicao = true;
}

$temaRepo = new TemaClubeRepository();
$temas = $temaRepo->listarAtivos();

View::render('clube-form', [
    'pageTitle' => ($modoEdicao ? 'Editar clube' : 'Criar clube') . ' — Laço de Papel',
    'activeNav' => 'meus-clubes',
    'useBands' => true,
    'modoEdicao' => $modoEdicao,
    'clube' => $clube,
    'temas' => $temas,
]);
