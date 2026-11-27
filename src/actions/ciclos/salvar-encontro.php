<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\CicloService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

Auth::exigirUsuario();
$idCiclo = (int) ($_POST['id_ciclo'] ?? 0);

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

$urlRetorno = 'ciclo.php?id=' . $idCiclo . '#encontro';
$encontroInput = trim((string) ($_POST['encontro_em'] ?? ''));
$encontroLocal = trim((string) ($_POST['encontro_local'] ?? ''));
$encontroUrl = trim((string) ($_POST['encontro_url'] ?? ''));

$encontroSql = null;
$validator = new Validator();

// Converte o formato datetime-local do navegador (com "T" e sem segundos) para DATETIME do MySQL
if ($encontroInput !== '') {
    $normalizado = str_replace('T', ' ', $encontroInput);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $normalizado)) {
        $candidato = strlen($normalizado) === 16 ? $normalizado . ':00' : $normalizado;
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $candidato);
        if ($dt !== false && $dt->format('Y-m-d H:i:s') === $candidato) {
            $encontroSql = $candidato;
        } else {
            $validator->adicionarErro('encontro_em', 'Informe data e hora válidas para o encontro.');
        }
    } else {
        $validator->adicionarErro('encontro_em', 'Informe data e hora válidas para o encontro.');
    }
}

if ($encontroLocal !== '') {
    $validator->tamanho('encontro_local', $encontroLocal, 2, 255, 'Local do encontro');
}
if ($encontroUrl !== '') {
    $validator->urlHttps('encontro_url', $encontroUrl, 'Link do encontro', false, 500);
}

if ($validator->falhou()) {
    Flash::erro($validator->primeiroErro() ?? 'Revise os dados do encontro.');
    redirect($urlRetorno);
}

// Apenas o responsável do clube pode agendar ou alterar o encontro enquanto o ciclo estiver aberto
try {
    $service = new CicloService();
    $service->salvarEncontro($idCiclo, $encontroSql, $encontroLocal, $encontroUrl);
    Flash::sucesso('Informações do encontro atualizadas.');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
}

redirect($urlRetorno);
