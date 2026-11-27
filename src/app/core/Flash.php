<?php

declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Guarda mensagens temporárias, erros de validação e inputs na sessão entre o POST e o redirect (PRG).
final class Flash
{
    private const KEY_MESSAGES = '_lp_flash_messages';
    private const KEY_ERRORS = '_lp_flash_errors';
    private const KEY_OLD = '_lp_flash_old';

    private const TIPOS_PERMITIDOS = ['sucesso', 'erro', 'aviso', 'info'];

    // Cache da requisição atual: consome da sessão uma única vez e reutiliza em cada campo do formulário
    private static ?array $errorsCache = null;
    private static ?array $oldCache = null;

    public static function add(string $tipo, string $mensagem): void
    {
        $tipoNormalizado = in_array($tipo, self::TIPOS_PERMITIDOS, true) ? $tipo : 'info';

        if (!isset($_SESSION[self::KEY_MESSAGES]) || !is_array($_SESSION[self::KEY_MESSAGES])) {
            $_SESSION[self::KEY_MESSAGES] = [];
        }

        $_SESSION[self::KEY_MESSAGES][] = [
            'tipo' => $tipoNormalizado,
            'mensagem' => $mensagem,
        ];
    }

    public static function sucesso(string $mensagem): void
    {
        self::add('sucesso', $mensagem);
    }

    public static function erro(string $mensagem): void
    {
        self::add('erro', $mensagem);
    }

    public static function aviso(string $mensagem): void
    {
        self::add('aviso', $mensagem);
    }

    public static function info(string $mensagem): void
    {
        self::add('info', $mensagem);
    }

    public static function consumeAll(): array
    {
        $messages = $_SESSION[self::KEY_MESSAGES] ?? [];
        unset($_SESSION[self::KEY_MESSAGES]);

        return is_array($messages) ? $messages : [];
    }

    public static function setErrors(array $errors): void
    {
        $_SESSION[self::KEY_ERRORS] = $errors;
        self::$errorsCache = null;
    }

    public static function getErrors(): array
    {
        if (self::$errorsCache === null) {
            $raw = $_SESSION[self::KEY_ERRORS] ?? [];
            unset($_SESSION[self::KEY_ERRORS]);
            self::$errorsCache = is_array($raw) ? $raw : [];
        }

        return self::$errorsCache;
    }

    public static function getError(string $field): ?string
    {
        $errors = self::getErrors();
        return isset($errors[$field]) ? (string) $errors[$field] : null;
    }

    public static function setOld(array $input): void
    {
        $safe = [];

        foreach ($input as $key => $value) {
            // Nunca persiste senhas nem tokens CSRF na sessão para repreenchimento
            $keyLower = strtolower((string) $key);
            if (str_contains($keyLower, 'senha') || str_contains($keyLower, 'csrf')) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $safe[$key] = $value;
            }
        }

        $_SESSION[self::KEY_OLD] = $safe;
        self::$oldCache = null;
    }

    public static function getOld(string $field, mixed $default = ''): mixed
    {
        if (self::$oldCache === null) {
            $raw = $_SESSION[self::KEY_OLD] ?? [];
            unset($_SESSION[self::KEY_OLD]);
            self::$oldCache = is_array($raw) ? $raw : [];
        }

        return self::$oldCache[$field] ?? $default;
    }
}
