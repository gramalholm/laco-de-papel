<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\View;

// Página institucional aberta, sem dados dinâmicos de banco
View::render('quem-somos', [
    'pageTitle' => 'Quem somos — Laço de Papel',
    'activeNav' => 'quem-somos',
    'useBands' => true,
]);
