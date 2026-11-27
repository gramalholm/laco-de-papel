<?php

declare(strict_types=1);

use App\Config\ConnectionFactory;
use App\Core\Flash;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) ConnectionFactory::getConfig('base_url', ''), '/');
        $cleanPath = ltrim($path, '/');

        if ($cleanPath === '') {
            return $base === '' ? '/' : $base . '/';
        }

        return ($base === '' ? '' : $base) . '/' . $cleanPath;
    }
}

if (!function_exists('redirect')) {
    // Redireciona só para rotas internas (HTTP 303 por padrão para PRG), evitando open redirect
    function redirect(string $path, int $status = 303): never
    {
        $trimmed = trim($path);

        if (
            $trimmed === ''
            || str_starts_with($trimmed, '//')
            || str_contains($trimmed, '\\')
            || preg_match('/[\r\n]/', $trimmed) === 1
            || preg_match('#^[a-z][a-z0-9+\-.]*:#i', $trimmed) === 1
        ) {
            $destino = url('index.php');
        } else {
            $destino = url($trimmed);
        }

        http_response_code($status);
        header('Location: ' . $destino);
        exit;
    }
}

if (!function_exists('old')) {
    function old(string $field, mixed $default = ''): string
    {
        $value = Flash::getOld($field, $default);
        return is_scalar($value) ? (string) $value : '';
    }
}

if (!function_exists('field_error')) {
    function field_error(string $field): ?string
    {
        return Flash::getError($field);
    }
}

if (!function_exists('nullIfEmpty')) {
    // Converte string vazia em null para evitar colisão em colunas opcionais com UNIQUE (ex.: isbn10, isbn13)
    function nullIfEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (!function_exists('slugify')) {
    // Gera slug ASCII com mapa manual para não depender da extensão intl
    function slugify(string $text): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
            'ç' => 'c', 'Ç' => 'c', 'ñ' => 'n', 'Ñ' => 'n',
        ];

        $normalized = strtr(trim($text), $map);
        $normalized = strtolower($normalized);
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '-', $normalized);
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'clube';
    }
}

if (!function_exists('formatarData')) {
    function formatarData(?string $data, bool $comHora = false): string
    {
        if ($data === null || trim($data) === '') {
            return '—';
        }

        try {
            $dt = new DateTimeImmutable($data);
            return $comHora ? $dt->format('d/m/Y \à\s H:i') : $dt->format('d/m/Y');
        } catch (Throwable) {
            return '—';
        }
    }
}

if (!function_exists('formatarDataHora')) {
    function formatarDataHora(?string $data): string
    {
        return formatarData($data, true);
    }
}

if (!function_exists('formatarPercentual')) {
    function formatarPercentual(float|int|null $valor): string
    {
        if ($valor === null) {
            return '0%';
        }

        $num = round(min(100.0, max(0.0, (float) $valor)), 2);
        $fmt = rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',');

        return $fmt . '%';
    }
}

if (!function_exists('calcularProgressoFisico')) {
    function calcularProgressoFisico(?int $paginaAtual, ?int $totalPaginas): ?float
    {
        if ($paginaAtual === null || $totalPaginas === null || $totalPaginas <= 0) {
            return null;
        }

        $pct = ($paginaAtual / $totalPaginas) * 100;
        return round(min(100.0, max(0.0, $pct)), 2);
    }
}

if (!function_exists('isPost')) {
    function isPost(): bool
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }
}

if (!function_exists('exigirPost')) {
    function exigirPost(string $fallbackPath = 'index.php'): void
    {
        if (!isPost()) {
            Flash::erro('Ação permitida apenas via envio de formulário.');
            redirect($fallbackPath);
        }
    }
}
