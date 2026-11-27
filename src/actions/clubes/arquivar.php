<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Services\ClubeService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

Auth::exigirUsuario();
$idClube = (int) ($_POST['id_clube'] ?? 0);

if ($idClube <= 0) {
    Flash::erro('Clube inválido.');
    redirect('meus-clubes.php');
}

// Arquiva o clube em vez de excluí-lo para manter o histórico de ciclos e anotações intacto
try {
    $service = new ClubeService();
    $service->arquivarClube($idClube);
    Flash::info('Clube arquivado. O histórico de leituras foi preservado.');
    redirect('meus-clubes.php');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect('clube.php?id=' . $idClube);
}
