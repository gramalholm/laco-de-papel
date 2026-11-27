<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

// Logout exige POST com CSRF para impedir desconexão forçada por links externos
exigirPost('index.php');
Csrf::exigirValido('index.php');

Auth::logout();

// Inicia sessão limpa apenas para carregar a mensagem flash de despedida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Flash::info('Você saiu da sua conta com segurança.');
redirect('index.php');
