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

$urlRetorno = 'clube.php?id=' . $idClube . '#membros';
$acao = trim((string) ($_POST['acao'] ?? 'convidar'));
$service = new ClubeService();

// Concentra envio de convite por apelido e cancelamento de convite pendente, sempre restritos ao responsável
try {
    if ($acao === 'cancelar') {
        $idAlvo = (int) ($_POST['id_usuario'] ?? 0);
        if ($idAlvo <= 0) {
            throw new DomainException('Convite inválido para cancelamento.');
        }
        $service->cancelarConvite($idClube, $idAlvo);
        Flash::info('Convite cancelado.');
        redirect($urlRetorno);
    }

    $apelido = trim((string) ($_POST['apelido'] ?? ''));
    $mensagem = $service->convidarPorApelido($idClube, $usuario['id_usuario'], $apelido);
    Flash::sucesso($mensagem);
    redirect($urlRetorno);
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect($urlRetorno);
}
