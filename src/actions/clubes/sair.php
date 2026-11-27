<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Services\ClubeService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

$usuario = Auth::exigirUsuario();
$idClube = (int) ($_POST['id_clube'] ?? 0);

if ($idClube <= 0) {
    Flash::erro('Clube inválido.');
    redirect('meus-clubes.php');
}

// A saída é bloqueada na ClubeService se o usuário for o único responsável ativo do clube
try {
    $service = new ClubeService();
    $service->sairDoClube($idClube, $usuario['id_usuario']);
    Flash::info('Você saiu do clube.');
    redirect('meus-clubes.php');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect('clube.php?id=' . $idClube);
}
