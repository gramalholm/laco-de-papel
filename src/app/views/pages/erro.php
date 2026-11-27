<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var int|null $httpCode
 * @var string|null $titulo
 * @var string|null $mensagem
 */

$httpCode = (int) ($httpCode ?? 404);
$titulo = (string) ($titulo ?? 'Página não encontrada');
$mensagem = (string) ($mensagem ?? 'O conteúdo solicitado não foi localizado ou você não tem permissão para visualizá-lo.');
?>
<section class="py-5" style="max-width: 580px;">
  <span class="lp-section-label mb-2">Erro <?= $httpCode ?></span>
  <h1 class="mb-3"><?= e($titulo) ?></h1>
  <p class="lp-muted mb-4"><?= e($mensagem) ?></p>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn-lp-primary" href="<?= e(url('index.php')) ?>">Voltar para o início</a>
    <a class="btn-lp-secondary" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
  </div>
</section>
