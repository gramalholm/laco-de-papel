<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Services\CicloService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

$usuario = Auth::exigirUsuario();
$idCiclo = (int) ($_POST['id_ciclo'] ?? 0);
$idIndicacao = (int) ($_POST['id_indicacao'] ?? 0);

if ($idCiclo <= 0 || $idIndicacao <= 0) {
    Flash::erro('Voto inválido.');
    redirect('meus-clubes.php');
}

// Cada membro ativo tem um voto por ciclo; votar de novo substitui a escolha anterior enquanto a votação estiver aberta
try {
    $service = new CicloService();
    $msg = $service->votar($idCiclo, $usuario['id_usuario'], $idIndicacao);
    Flash::sucesso($msg);
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
}

redirect('ciclo.php?id=' . $idCiclo);
