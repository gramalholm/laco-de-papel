<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Services\ClubeService;

exigirPost('explorar-clubes.php');
Csrf::exigirValido('explorar-clubes.php');

$usuario = Auth::exigirUsuario();
$idClube = (int) ($_POST['id_clube'] ?? 0);

if ($idClube <= 0) {
    Flash::erro('Clube inválido.');
    redirect('explorar-clubes.php');
}

// Entrada direta vale apenas para clubes públicos ativos; clubes privados exigem convite
try {
    $service = new ClubeService();
    $mensagem = $service->entrarEmClubePublico($idClube, $usuario['id_usuario']);
    Flash::sucesso($mensagem);
    redirect('clube.php?id=' . $idClube);
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect('explorar-clubes.php');
}
