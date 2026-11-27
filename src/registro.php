<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;

// Quem já está autenticado não precisa acessar a tela de cadastro
Auth::exigirVisitante('index.php');

View::render('registro', [
    'pageTitle' => 'Criar conta — Laço de Papel',
    'activeNav' => 'registro',
    'useBands' => true,
]);
