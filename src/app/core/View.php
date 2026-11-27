<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class View
{
    private const BASE_PATH = __DIR__ . '/../views';

    public static function render(string $pageView, array $data = [], string $layout = 'main'): void
    {
        $pageFile = self::BASE_PATH . '/pages/' . ltrim($pageView, '/') . '.php';
        $layoutFile = self::BASE_PATH . '/layouts/' . ltrim($layout, '/') . '.php';

        if (!is_file($pageFile)) {
            throw new RuntimeException('View de página não encontrada: ' . $pageView);
        }

        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout não encontrado: ' . $layout);
        }

        // EXTR_SKIP impede que chaves de $data sobrescrevam $pageFile ou $layoutFile
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            require $pageFile;
            $content = (string) ob_get_clean();
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        require $layoutFile;
    }

    public static function component(string $componentName, array $data = []): void
    {
        $componentFile = self::BASE_PATH . '/components/' . ltrim($componentName, '/') . '.php';

        if (!is_file($componentFile)) {
            throw new RuntimeException('Componente não encontrado: ' . $componentName);
        }

        extract($data, EXTR_SKIP);
        require $componentFile;
    }

    public static function renderError(int $httpCode, string $titulo, string $mensagem): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code($httpCode);
        }

        $errorView = self::BASE_PATH . '/pages/erro.php';
        $layoutFile = self::BASE_PATH . '/layouts/main.php';

        if (is_file($errorView) && is_file($layoutFile)) {
            self::render('erro', [
                'pageTitle' => $titulo . ' — Laço de Papel',
                'activeNav' => '',
                'httpCode' => $httpCode,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
            ]);
            exit;
        }

        // Fallback em HTML puro caso o erro aconteça sem acesso aos templates
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>'
            . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8')
            . '</title></head><body style="font-family:sans-serif;padding:2rem;background:#F3EBDD;color:#302820">'
            . '<h1>' . $httpCode . ' — ' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</body></html>';
        exit;
    }
}
