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

$usuario = Auth::exigirUsuario();
$idCiclo = (int) ($_POST['id_ciclo'] ?? 0);
$idLivro = (int) ($_POST['id_livro'] ?? 0);
$justificativa = trim((string) ($_POST['justificativa'] ?? ''));

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

$urlRetorno = 'ciclo.php?id=' . $idCiclo;

$validator = new Validator();
if ($idLivro <= 0) {
    $validator->adicionarErro('id_livro', 'Selecione um livro do acervo para indicar.');
}
if ($justificativa !== '') {
    $validator->tamanho('justificativa', $justificativa, 3, 500, 'Justificativa');
}

if ($validator->falhou()) {
    Flash::erro($validator->primeiroErro() ?? 'Selecione um livro válido para indicar.');
    redirect($urlRetorno);
}

// Permite indicação apenas a membros ativos durante votação aberta, bloqueando livros duplicados no ciclo
try {
    $service = new CicloService();
    $service->indicarLivro($idCiclo, $usuario['id_usuario'], $idLivro, $justificativa);
    Flash::sucesso('Livro indicado para a votação do clube!');
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
}

redirect($urlRetorno);
