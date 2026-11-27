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
// Em caso de empate, o responsável pode enviar a indicação escolhida para definir o livro do ciclo
$idDesempate = (int) ($_POST['id_indicacao_vencedora'] ?? 0);

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

try {
    $service = new CicloService();
    $msg = $service->encerrarVotacao($idCiclo, $idDesempate > 0 ? $idDesempate : null);
    Flash::sucesso($msg);
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
}

redirect('ciclo.php?id=' . $idCiclo);
