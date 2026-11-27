<?php

declare(strict_types=1);

namespace App\Config;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Reutiliza uma única conexão PDO ao longo da requisição.
final class ConnectionFactory
{
    private static ?PDO $connection = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
        self::$connection = null;
    }

    public static function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }

        return self::$config[$key] ?? $default;
    }

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        if (self::$config === []) {
            throw new RuntimeException('Configuração da aplicação não inicializada.');
        }

        $host = (string) (self::$config['db_host'] ?? '127.0.0.1');
        $port = (int) (self::$config['db_port'] ?? 3306);
        $dbname = (string) (self::$config['db_name'] ?? '');
        $charset = (string) (self::$config['db_charset'] ?? 'utf8mb4');
        $user = (string) (self::$config['db_user'] ?? '');
        $pass = (string) (self::$config['db_pass'] ?? '');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $dbname,
            $charset
        );

        try {
            // Desliga emulação para usar prepared statements nativos e preservar tipos retornados pelo MySQL
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);

            // Alinha o fuso horário da sessão MySQL (NOW/CURDATE) ao fuso configurado no PHP
            try {
                $tz = (string) (self::$config['timezone'] ?? 'America/Sao_Paulo');
                $offset = (new DateTimeImmutable('now', new DateTimeZone($tz)))->format('P');
                self::$connection->exec("SET time_zone = '" . $offset . "'");
            } catch (Throwable) {
                // Mantém o fuso padrão do banco se a hospedagem restringir SET time_zone
            }
        } catch (PDOException $e) {
            // Registra o erro real no log sem expor host ou credenciais na mensagem exibida
            error_log('[Laço de Papel] Erro ao conectar ao banco de dados: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível conectar ao banco de dados no momento.');
        }

        return self::$connection;
    }
}
