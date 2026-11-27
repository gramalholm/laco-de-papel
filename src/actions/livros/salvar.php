<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Config\ConnectionFactory;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\EdicaoRepository;
use App\Models\LivroRepository;

exigirPost('acervo.php');
Csrf::exigirValido('acervo.php');

$usuario = Auth::exigirUsuario();

$idLivro = (int) ($_POST['id_livro'] ?? 0);
$modoEdicao = $idLivro > 0;
$urlRetorno = $modoEdicao ? 'atualizar.php?tipo=livro&id=' . $idLivro : 'acervo.php?novo=1';

$livroRepo = new LivroRepository();

// Edição de obra existente fica restrita a quem a cadastrou no acervo
if ($modoEdicao) {
    $livroExistente = $livroRepo->buscarPorId($idLivro);
    if ($livroExistente === null) {
        Flash::erro('Livro não encontrado.');
        redirect('acervo.php');
    }
    if ((int) ($livroExistente['criado_por'] ?? 0) !== $usuario['id_usuario']) {
        Flash::erro('Apenas quem cadastrou este livro pode editar seus dados.');
        redirect('livro.php?id=' . $idLivro);
    }
}

$titulo = trim((string) ($_POST['titulo'] ?? ''));
$autorPrincipal = trim((string) ($_POST['autor_principal'] ?? ''));
$ano = trim((string) ($_POST['ano_primeira_publicacao'] ?? ''));
$idioma = trim((string) ($_POST['idioma_original'] ?? ''));
$capaUrl = trim((string) ($_POST['capa_url'] ?? ''));
$descricao = trim((string) ($_POST['descricao'] ?? ''));
$incluirEdicao = !$modoEdicao && (($_POST['incluir_edicao'] ?? '') === '1');

$editora = trim((string) ($_POST['editora'] ?? ''));
$totalPaginas = trim((string) ($_POST['total_paginas'] ?? ''));
// Normaliza o ISBN-13 removendo espaços e hífens antes de validar formato e unicidade
$isbn13 = strtoupper(preg_replace('/[\s-]+/', '', (string) ($_POST['isbn13'] ?? '')) ?? '');

$validator = (new Validator())
    ->obrigatorio('titulo', $titulo, 'Título da obra')
    ->tamanho('titulo', $titulo, 1, 250, 'Título da obra')
    ->obrigatorio('autor_principal', $autorPrincipal, 'Autor principal')
    ->tamanho('autor_principal', $autorPrincipal, 2, 180, 'Autor principal');

if ($ano !== '') {
    $validator->inteiro('ano_primeira_publicacao', $ano, 1000, 2100, 'Ano original');
}
if ($idioma !== '') {
    $validator->tamanho('idioma_original', $idioma, 2, 20, 'Idioma');
}
if ($capaUrl !== '') {
    $validator->urlHttps('capa_url', $capaUrl, 'URL da capa', false, 700);
}
if ($descricao !== '') {
    $validator->tamanho('descricao', $descricao, 3, 3000, 'Sinopse');
}

$edicaoRepo = new EdicaoRepository();
if ($incluirEdicao) {
    if ($editora !== '') {
        $validator->tamanho('editora', $editora, 2, 180, 'Editora');
    }
    if ($totalPaginas !== '') {
        $validator->inteiro('total_paginas', $totalPaginas, 1, 10000, 'Total de páginas');
    }
    if ($isbn13 !== '') {
        if (!preg_match('/^\d{13}$/', $isbn13)) {
            $validator->adicionarErro('isbn13', 'O ISBN-13 deve conter exatamente 13 números.');
        } elseif ($edicaoRepo->existeIsbn13($isbn13)) {
            $validator->adicionarErro('isbn13', 'Já existe uma edição cadastrada com este ISBN-13.');
        }
    }
}

if ($validator->falhou()) {
    Flash::setErrors($validator->erros());
    Flash::setOld($_POST);
    Flash::erro($validator->primeiroErro() ?? 'Revise os campos destacados para salvar o livro.');
    redirect($urlRetorno);
}

$dadosLivro = [
    'titulo' => $titulo,
    'autor_principal' => $autorPrincipal,
    'ano_primeira_publicacao' => $ano,
    'idioma_original' => $idioma,
    'capa_url' => $capaUrl,
    'descricao' => $descricao,
    'fonte_metadados' => 'manual',
    'criado_por' => $usuario['id_usuario'],
];

if ($modoEdicao) {
    $livroRepo->atualizar($idLivro, $dadosLivro);
    Flash::sucesso('Os dados do livro foram atualizados.');
    redirect('livro.php?id=' . $idLivro);
}

// Cadastro de obra + primeira edição física roda em transação para não deixar o livro salvo sem a edição em caso de erro
$pdo = ConnectionFactory::getConnection();
$pdo->beginTransaction();

try {
    $novoIdLivro = $livroRepo->criar($dadosLivro);

    if ($incluirEdicao && ($editora !== '' || $totalPaginas !== '' || $isbn13 !== '')) {
        $edicaoRepo->criar([
            'id_livro' => $novoIdLivro,
            'editora' => $editora,
            'total_paginas' => $totalPaginas,
            'isbn13' => $isbn13,
            'idioma' => $idioma !== '' ? $idioma : 'pt-BR',
            'fonte_metadados' => 'manual',
            'criado_por' => $usuario['id_usuario'],
        ]);
    }

    $pdo->commit();
    Flash::sucesso('Livro cadastrado no acervo com sucesso.');
    redirect('livro.php?id=' . $novoIdLivro);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
