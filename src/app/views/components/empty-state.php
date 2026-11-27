<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var string|null $titulo
 * @var string|null $mensagem
 * @var string|null $ctaTexto
 * @var string|null $ctaUrl
 */

$titulo = (string) ($titulo ?? 'Nada por aqui ainda');
$mensagem = (string) ($mensagem ?? '');
$ctaTexto = isset($ctaTexto) ? (string) $ctaTexto : '';
$ctaUrl = isset($ctaUrl) ? (string) $ctaUrl : '';
?>
<div class="lp-empty-state">
  <h2 class="h4 mb-2"><?= e($titulo) ?></h2>
  <?php if ($mensagem !== ''): ?>
    <p class="lp-muted mb-3 mx-auto" style="max-width: 520px;"><?= e($mensagem) ?></p>
  <?php endif; ?>
  <?php if ($ctaTexto !== '' && $ctaUrl !== ''): ?>
    <a class="btn-lp-primary btn-lp-sm" href="<?= e(url($ctaUrl)) ?>"><?= e($ctaTexto) ?></a>
  <?php endif; ?>
</div>
