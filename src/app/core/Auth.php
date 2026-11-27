<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\ConnectionFactory;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class Auth
{
    private const SESSION_KEY = '_lp_auth_user';
    private const INTENDED_KEY = '_lp_intended_url';

    public static function login(array $usuario): void
    {
        // Regenera o ID da sessão no login para evitar session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION[self::SESSION_KEY] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nome' => (string) $usuario['nome'],
            'apelido' => (string) $usuario['apelido'],
            'email' => (string) $usuario['email'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?: '',
                    'secure' => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?: 'Lax',
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        $user = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($user) || empty($user['id_usuario'])) {
            return null;
        }

        $id = (int) $user['id_usuario'];
        return $id > 0 ? $id : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $user = $_SESSION[self::SESSION_KEY];
        return $user;
    }

    public static function exigirUsuario(bool $jsonResponse = false): array
    {
        $user = self::user();
        if ($user !== null) {
            return $user;
        }

        if ($jsonResponse) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'erro' => 'Você precisa estar autenticado para realizar esta ação.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Salva a rota GET original para voltar após o login, aceitando apenas caminhos relativos internos
        if (!isPost() && !empty($_SERVER['REQUEST_URI'])) {
            $uri = (string) $_SERVER['REQUEST_URI'];
            if (
                str_starts_with($uri, '/')
                && !str_starts_with($uri, '//')
                && !str_contains($uri, '\\')
                && preg_match('/[\r\n]/', $uri) !== 1
            ) {
                $base = rtrim((string) ConnectionFactory::getConfig('base_url', ''), '/');
                if ($base !== '' && str_starts_with($uri, $base . '/')) {
                    $uri = substr($uri, strlen($base));
                }
                $_SESSION[self::INTENDED_KEY] = ltrim($uri, '/');
            }
        }

        Flash::aviso('Faça login para acessar esta área.');
        redirect('login.php');
    }

    public static function consumirDestinoPosLogin(string $padrao = 'meus-clubes.php'): string
    {
        $intended = $_SESSION[self::INTENDED_KEY] ?? null;
        unset($_SESSION[self::INTENDED_KEY]);

        if (!is_string($intended) || trim($intended) === '') {
            return $padrao;
        }

        // Ignora URLs externas, barras invertidas ou quebras de linha para evitar open redirect
        if (
            str_starts_with($intended, '//')
            || str_contains($intended, '\\')
            || preg_match('/[\r\n]/', $intended) === 1
            || preg_match('#^[a-z][a-z0-9+\-.]*:#i', $intended) === 1
        ) {
            return $padrao;
        }

        return $intended;
    }

    public static function exigirVisitante(string $redirectPath = 'meus-clubes.php'): void
    {
        if (self::check()) {
            redirect($redirectPath);
        }
    }
}
