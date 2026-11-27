<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array $clube
 * @var int|null $idCicloAtivo
 * @var array|null $cicloAtual
 * @var int|null $totalMembros
 * @var string|null $abaAtiva
 * @var bool|null $mostrarAbas
 */

$abaAtiva = (string) ($abaAtiva ?? 'visao');
$preset = (string) ($clube['cor_identidade'] ?? 'vinho');
$idClube = (int) ($clube['id_clube'] ?? 0);
$idCicloNav = isset($idCicloAtivo) ? (int) $idCicloAtivo : (int) ($cicloAtual['id_ciclo'] ?? 0);
$totalMembros = isset($totalMembros) ? (int) $totalMembros : null;
$corPonto = match ($preset) {
    'verde' => 'var(--lp-moss)',
    'madeira' => 'var(--lp-wood)',
    default => 'var(--lp-burgundy)',
};
?>
<header class="lp-club-masthead">
  <div class="lp-container">
    <div class="d-flex flex-wrap align-items-center gap-2 lp-meta-line mb-2">
      <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background-color: <?= $corPonto ?>;" aria-hidden="true"></span>
      <?php if (!empty($clube['tema_nome'])): ?>
        <span><?= e((string) $clube['tema_nome']) ?></span>
        <span>&mdash;</span>
      <?php endif; ?>
      <span><?= ($clube['visibilidade'] ?? '') === 'privado' ? 'Clube privado' : 'Clube público' ?></span>
      <?php if ($totalMembros !== null): ?>
        <span>&mdash;</span>
        <span><?= $totalMembros ?> <?= $totalMembros === 1 ? 'participante' : 'participantes' ?></span>
      <?php endif; ?>
      <?php if (($clube['status'] ?? 'ativo') === 'arquivado'): ?>
        <span>&mdash;</span>
        <span style="color: var(--lp-burgundy); font-weight: 600;">Arquivado</span>
      <?php endif; ?>
    </div>

    <h1 class="mb-2"><?= e((string) ($clube['nome'] ?? '')) ?></h1>

    <?php if (!empty($clube['descricao'])): ?>
      <p class="lp-muted mb-0" style="max-width: 62ch; font-size: 1.02rem;">
        <?= e((string) $clube['descricao']) ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($mostrarAbas)): ?>
      <nav class="lp-club-masthead-tabs" aria-label="Navegação do clube">
        <a class="lp-club-masthead-tab<?= $abaAtiva === 'visao' ? ' is-active' : '' ?>" href="<?= e(url('clube.php?id=' . $idClube)) ?>">
          Visão geral
        </a>
        <?php if ($idCicloNav > 0): ?>
          <a class="lp-club-masthead-tab<?= $abaAtiva === 'ciclo' ? ' is-active' : '' ?>" href="<?= e(url('ciclo.php?id=' . $idCicloNav)) ?>">
            Sala do ciclo
          </a>
          <a class="lp-club-masthead-tab<?= $abaAtiva === 'caderno' ? ' is-active' : '' ?>" href="<?= e(url('caderno.php?id=' . $idCicloNav)) ?>">
            Caderno de Margens
          </a>
        <?php endif; ?>
      </nav>
    <?php else: ?>
      <div class="pb-4"></div>
    <?php endif; ?>
  </div>
</header>
