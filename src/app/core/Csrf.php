<?php

declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Token único por sessão, aceito via campo POST (formulários) ou cabeçalho X-CSRF-Token (fetch).
final class Csrf
{
    private const SESSION_KEY = '_lp_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function metaTag(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<meta name="csrf-token" content="' . $token . '">';
    }

    public static function extractToken(): ?string
    {
        if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return null;
    }

    public static function validate(?string $submittedToken = null): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $token = $submittedToken ?? self::extractToken();
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function exigirValido(?string $redirectPath = null, bool $jsonResponse = false): void
    {
        if (self::validate()) {
            return;
        }

        if ($jsonResponse) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'erro' => 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        Flash::erro('Sessão expirada ou formulário inválido. Tente novamente.');
        redirect($redirectPath ?? 'index.php');
    }
}
