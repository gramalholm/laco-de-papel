<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\View;

// Evita exibir a tela de login para quem já possui sessão ativa
Auth::exigirVisitante('index.php');

View::render('login', [
    'pageTitle' => 'Entrar — Laço de Papel',
    'activeNav' => 'login',
    'useBands' => true,
]);
