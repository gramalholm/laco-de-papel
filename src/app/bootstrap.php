<?php

declare(strict_types=1);

use App\Config\ConnectionFactory;
use App\Core\View;

// Impede acesso direto pela URL aos arquivos internos em src/app/
if (!defined('APP_RUNNING')) {
    define('APP_RUNNING', true);
}

// Autoloader sem Composer; prefixos com case exato para não quebrar no Linux
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Config\\' => __DIR__ . '/config/',
        'App\\Core\\' => __DIR__ . '/core/',
        'App\\Models\\' => __DIR__ . '/models/',
        'App\\Services\\' => __DIR__ . '/services/',
        'App\\Integrations\\' => __DIR__ . '/integrations/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
        return;
    }
});

$configPath = __DIR__ . '/config/app.local.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Arquivo src/app/config/app.local.php não encontrado. Copie app.example.php e informe as credenciais do banco.');
}

$config = require $configPath;
ConnectionFactory::init($config);

$timezone = (string) ($config['timezone'] ?? 'America/Sao_Paulo');
date_default_timezone_set($timezone);

$isDebug = (bool) ($config['debug'] ?? false);
error_reporting(E_ALL);
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');

// Sessão apenas via cookie; checa X-Forwarded-Proto para funcionar atrás de proxy HTTPS
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );

    session_name('LP_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/core/Helpers.php';

// Evita expor stack trace fora do modo debug e devolve JSON nas rotas em /api/
if (PHP_SAPI !== 'cli') {
    set_exception_handler(static function (Throwable $e) use ($isDebug): void {
        error_log('[Laço de Papel] Exceção não capturada: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $isApiRequest = str_contains((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/api/');
        if ($isApiRequest) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'erro' => $isDebug ? $e->getMessage() : 'Erro interno ao processar a solicitação.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $mensagem = $isDebug
            ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
            : 'Ocorreu um erro inesperado ao processar sua solicitação. Tente novamente em instantes.';

        View::renderError(500, 'Erro interno', $mensagem);
    });
}
