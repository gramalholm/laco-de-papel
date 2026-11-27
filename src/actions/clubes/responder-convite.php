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
$decisao = trim((string) ($_POST['decisao'] ?? ''));

if ($idClube <= 0) {
    Flash::erro('Convite inválido.');
    redirect('meus-clubes.php');
}

// Aceitar redireciona direto para a página do clube; recusar mantém o leitor na lista de clubes
try {
    $service = new ClubeService();
    $mensagem = $service->responderConvite($idClube, $usuario['id_usuario'], $decisao);
    Flash::sucesso($mensagem);

    if ($decisao === 'aceitar') {
        redirect('clube.php?id=' . $idClube);
    }
    redirect('meus-clubes.php');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect('meus-clubes.php');
}
