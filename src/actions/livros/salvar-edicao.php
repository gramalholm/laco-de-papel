<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\EdicaoRepository;
use App\Models\LivroRepository;

exigirPost('acervo.php');
Csrf::exigirValido('acervo.php');

$usuario = Auth::exigirUsuario();

$idEdicao = (int) ($_POST['id_edicao'] ?? 0);
$idLivro = (int) ($_POST['id_livro'] ?? 0);
$modoEdicao = $idEdicao > 0;

$edicaoRepo = new EdicaoRepository();
$livroRepo = new LivroRepository();

$edicaoAtual = null;

// Na edição, ignora o id_livro do POST e usa o vínculo salvo no banco para impedir troca de obra
if ($modoEdicao) {
    $edicaoAtual = $edicaoRepo->buscarPorId($idEdicao);
    if ($edicaoAtual === null) {
        Flash::erro('Edição não encontrada.');
        redirect('acervo.php');
    }
    if ((int) ($edicaoAtual['criado_por'] ?? 0) !== $usuario['id_usuario']) {
        Flash::erro('Apenas quem cadastrou esta edição pode editá-la.');
        redirect('livro.php?id=' . (int) $edicaoAtual['id_livro']);
    }
    $idLivro = (int) $edicaoAtual['id_livro'];
} else {
    if ($idLivro <= 0 || $livroRepo->buscarPorId($idLivro) === null) {
        Flash::erro('Livro não encontrado.');
        redirect('acervo.php');
    }
}

$urlRetorno = $modoEdicao ? 'atualizar.php?tipo=edicao&id=' . $idEdicao : 'livro.php?id=' . $idLivro;

$tituloEdicao = trim((string) ($_POST['titulo_edicao'] ?? ''));
$editora = trim((string) ($_POST['editora'] ?? ''));
$totalPaginas = trim((string) ($_POST['total_paginas'] ?? ''));
$ano = trim((string) ($_POST['ano_publicacao'] ?? ''));
$idioma = trim((string) ($_POST['idioma'] ?? ''));
$capaUrl = array_key_exists('capa_url', $_POST)
    ? trim((string) $_POST['capa_url'])
    : (string) ($edicaoAtual['capa_url'] ?? '');
$isbn13 = strtoupper(preg_replace('/[\s-]+/', '', (string) ($_POST['isbn13'] ?? '')) ?? '');
$isbn10 = strtoupper(preg_replace('/[\s-]+/', '', (string) ($_POST['isbn10'] ?? '')) ?? '');

$validator = (new Validator())
    ->obrigatorio('editora', $editora, 'Editora')
    ->tamanho('editora', $editora, 2, 180, 'Editora')
    ->obrigatorio('total_paginas', $totalPaginas, 'Total de páginas')
    ->inteiro('total_paginas', $totalPaginas, 1, 10000, 'Total de páginas');

if ($tituloEdicao !== '') {
    $validator->tamanho('titulo_edicao', $tituloEdicao, 2, 250, 'Nome da edição');
}
if ($ano !== '') {
    $validator->inteiro('ano_publicacao', $ano, 1450, 2100, 'Ano da edição');
}
if ($idioma !== '') {
    $validator->tamanho('idioma', $idioma, 2, 20, 'Idioma');
}
if ($capaUrl !== '') {
    $validator->urlHttps('capa_url', $capaUrl, 'URL da capa', false, 700);
}
// Valida formato e unicidade dos ISBNs ignorando a própria edição quando está em modo de edição
if ($isbn13 !== '') {
    if (!preg_match('/^\d{13}$/', $isbn13)) {
        $validator->adicionarErro('isbn13', 'O ISBN-13 deve conter exatamente 13 números.');
    } elseif ($edicaoRepo->existeIsbn13($isbn13, $modoEdicao ? $idEdicao : null)) {
        $validator->adicionarErro('isbn13', 'Este ISBN-13 já está cadastrado em outra edição.');
    }
}
if ($isbn10 !== '') {
    if (!preg_match('/^\d{9}[\dX]$/', $isbn10)) {
        $validator->adicionarErro('isbn10', 'O ISBN-10 deve conter 10 caracteres (9 números + dígito ou X).');
    } elseif ($edicaoRepo->existeIsbn10($isbn10, $modoEdicao ? $idEdicao : null)) {
        $validator->adicionarErro('isbn10', 'Este ISBN-10 já está cadastrado em outra edição.');
    }
}

if ($validator->falhou()) {
    Flash::setErrors($validator->erros());
    Flash::setOld($_POST);
    Flash::erro($validator->primeiroErro() ?? 'Revise os dados da edição antes de salvar.');
    redirect($urlRetorno);
}

$dados = [
    'id_livro' => $idLivro,
    'titulo_edicao' => $tituloEdicao,
    'editora' => $editora,
    'total_paginas' => $totalPaginas,
    'ano_publicacao' => $ano,
    'idioma' => $idioma,
    'capa_url' => $capaUrl,
    'isbn13' => $isbn13,
    'isbn10' => $isbn10,
    'fonte_metadados' => 'manual',
    'criado_por' => $usuario['id_usuario'],
];

if ($modoEdicao) {
    $edicaoRepo->atualizar($idEdicao, $dados);
    Flash::sucesso('Edição atualizada com sucesso.');
} else {
    $edicaoRepo->criar($dados);
    Flash::sucesso('Edição física cadastrada com sucesso.');
}

redirect('livro.php?id=' . $idLivro);
