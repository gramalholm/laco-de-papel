<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Services\CicloService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

Auth::exigirUsuario();
$idCiclo = (int) ($_POST['id_ciclo'] ?? 0);

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

// Validação de responsável e bloqueio de re-encerramento ficam concentrados na CicloService
try {
    $service = new CicloService();
    $service->encerrarCiclo($idCiclo);
    Flash::sucesso('Ciclo encerrado com sucesso! Ele agora faz parte do histórico do clube.');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
}

redirect('ciclo.php?id=' . $idCiclo);
