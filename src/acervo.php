<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;
use App\Models\LivroRepository;

$termo = is_scalar($_GET['q'] ?? null) ? trim((string) $_GET['q']) : '';

$livroRepo = new LivroRepository();
$livros = $livroRepo->buscar($termo !== '' ? $termo : null, 40);

// Reabre o formulário de cadastro automaticamente após erro de validação ou via ?novo=1
$abrirCadastro = (
    ($_GET['novo'] ?? '') === '1'
    || old('titulo') !== ''
    || old('autor_principal') !== ''
    || field_error('titulo') !== null
    || field_error('autor_principal') !== null
    || field_error('ano_primeira_publicacao') !== null
    || field_error('idioma_original') !== null
    || field_error('capa_url') !== null
    || field_error('descricao') !== null
    || field_error('editora') !== null
    || field_error('total_paginas') !== null
    || field_error('isbn13') !== null
    || field_error('isbn10') !== null
);

View::render('acervo', [
    'pageTitle' => 'Acervo de livros — Laço de Papel',
    'activeNav' => 'acervo',
    'useBands' => true,
    'scripts' => ['assets/js/catalog.js'],
    'usuario' => Auth::user(),
    'termo' => $termo,
    'livros' => $livros,
    'abrirCadastro' => $abrirCadastro,
]);
