<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Services\LeituraService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

$usuario = Auth::exigirUsuario();
$idCiclo = is_scalar($_POST['id_ciclo'] ?? null) ? (int) $_POST['id_ciclo'] : 0;
$origem = is_scalar($_POST['origem'] ?? null) ? trim((string) $_POST['origem']) : 'ciclo';

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

// O progresso pode ser salvo tanto na Sala do Ciclo quanto no Caderno; retorna para a tela de origem
$urlRetorno = $origem === 'caderno' ? 'caderno.php?id=' . $idCiclo : 'ciclo.php?id=' . $idCiclo;

$rawKindle = $_POST['percentual_kindle'] ?? null;
$percentualKindle = is_scalar($rawKindle) ? str_replace(',', '.', trim((string) $rawKindle)) : null;

// Normaliza vírgula decimal do Kindle; a conversão de páginas físicas em percentual fica na LeituraService
try {
    $service = new LeituraService();
    $pct = $service->salvarProgresso($idCiclo, $usuario['id_usuario'], [
        'formato' => is_scalar($_POST['formato'] ?? null) ? trim((string) $_POST['formato']) : '',
        'id_edicao' => $_POST['id_edicao'] ?? null,
        'pagina_atual' => $_POST['pagina_atual'] ?? null,
        'percentual_kindle' => $percentualKindle,
        'status_leitura' => is_scalar($_POST['status_leitura'] ?? null) ? trim((string) $_POST['status_leitura']) : 'lendo',
    ]);

    Flash::sucesso('Progresso atualizado para ' . formatarPercentual($pct) . '.');
} catch (DomainException $e) {
    Flash::setOld($_POST);
    Flash::erro($e->getMessage());
}

redirect($urlRetorno);
