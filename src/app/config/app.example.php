<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Modelo de configuração. Copie para app.local.php e ajuste para o seu ambiente.
return [
    'app_name' => 'Laço de Papel',
    'app_slogan' => 'Histórias aproximam. Leituras criam laços.',
    'base_url' => '',
    'debug' => true,
    'timezone' => 'America/Sao_Paulo',

    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'laco_de_papel',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
];
