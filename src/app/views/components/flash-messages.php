<?php

declare(strict_types=1);

use App\Core\Flash;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

$messages = Flash::consumeAll();
if ($messages === []) {
    return;
}

$rotulos = [
    'sucesso' => 'Sucesso',
    'erro' => 'Atenção',
    'aviso' => 'Aviso',
    'info' => 'Informação',
];
?>
<div class="lp-flash-stack" aria-live="polite">
  <?php foreach ($messages as $msg): ?>
    <?php
      $tipo = (string) ($msg['tipo'] ?? 'info');
      $rotulo = $rotulos[$tipo] ?? 'Informação';
    ?>
    <div class="alert lp-alert lp-alert-<?= e($tipo) ?> alert-dismissible fade show" role="alert">
      <div>
        <strong><?= e($rotulo) ?>:</strong>
        <?= e((string) ($msg['mensagem'] ?? '')) ?>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
    </div>
  <?php endforeach; ?>
</div>
