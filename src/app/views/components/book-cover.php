<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var string|null $titulo
 * @var string|null $autor
 * @var int|string|null $ano
 * @var string|null $capaUrl
 * @var int|null $tomIndex
 */

$titulo = (string) ($titulo ?? 'Sem título');
$autor = (string) ($autor ?? '');
$ano = isset($ano) && $ano !== null && $ano !== '' ? (string) $ano : '';
$capaUrl = isset($capaUrl) && is_string($capaUrl) ? trim($capaUrl) : '';
$temCapa = $capaUrl !== '' && str_starts_with(strtolower($capaUrl), 'https://');

$tons = ['cloth-musgo', 'cloth-vinho', 'cloth-nogueira', 'cloth-papel'];
$indiceTom = isset($tomIndex)
    ? abs((int) $tomIndex) % count($tons)
    : abs(crc32($titulo . '|' . $autor)) % count($tons);
$classeTom = $tons[$indiceTom];
?>
<div class="lp-book-cover">
  <?php if ($temCapa): ?>
    <img
      src="<?= e($capaUrl) ?>"
      alt="<?= e('Capa do livro ' . $titulo) ?>"
      loading="lazy"
      data-fallback-src="<?= e(url('assets/img/capa-placeholder.svg')) ?>"
    >
  <?php else: ?>
    <div class="lp-book-cloth <?= e($classeTom) ?>">
      <div class="lp-book-cloth-inner">
        <div class="lp-book-cloth-top">
          <?php if ($autor !== ''): ?>
            <span class="lp-book-cloth-author"><?= e($autor) ?></span>
          <?php endif; ?>
        </div>

        <div class="lp-book-cloth-title"><?= e($titulo) ?></div>

        <div class="lp-book-cloth-foot">
          <span><?= $ano !== '' ? e($ano) : 'Acervo' ?></span>
          <span>sem capa</span>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
