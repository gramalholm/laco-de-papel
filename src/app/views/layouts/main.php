<?php

declare(strict_types=1);

use App\Core\View;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var bool|null $useBands
 * @var string|null $content
 */

$useBands = !empty($useBands);

require __DIR__ . '/header.php';
?>
<main class="lp-main<?= $useBands ? ' lp-main-bands' : '' ?>">
  <?php if ($useBands): ?>
    <div class="lp-container">
      <?php View::component('flash-messages'); ?>
    </div>
    <?= $content ?? '' ?>
  <?php else: ?>
    <div class="lp-container">
      <?php View::component('flash-messages'); ?>
      <?= $content ?? '' ?>
    </div>
  <?php endif; ?>
</main>
<?php
require __DIR__ . '/footer.php';
