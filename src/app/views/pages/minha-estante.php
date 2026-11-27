<?php

declare(strict_types=1);

use App\Core\View;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array $usuario
 * @var string $statusFiltro
 * @var array $contagens
 * @var array $leituras
 */

$rotuloStatus = static function (string $status): string {
    return match ($status) {
        'lendo' => 'Em leitura',
        'concluido' => 'Concluído',
        'pausado' => 'Pausado',
        default => $status,
    };
};
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <span class="lp-section-label lp-section-label-moss">Histórico pessoal</span>
        <h1 class="mt-1 mb-2">Minha estante</h1>
        <p class="lp-muted mb-0" style="max-width: 56ch;">
          Todas as leituras acompanhadas por @<?= e($usuario['apelido']) ?> nos clubes em que você participa, cada ciclo preservado no seu ritmo.
        </p>
      </div>
      <a class="btn-lp-secondary btn-lp-sm" href="<?= e(url('meus-clubes.php')) ?>">Ver meus clubes</a>
    </div>

    <nav class="lp-filter-nav pb-2" style="border-bottom: 1px solid var(--lp-border);" aria-label="Filtrar estante por situação">
      <a class="lp-filter-link<?= $statusFiltro === '' ? ' is-active' : '' ?>" href="<?= e(url('minha-estante.php')) ?>">
        Todas (<?= $contagens['todas'] ?>)
      </a>
      <a class="lp-filter-link<?= $statusFiltro === 'lendo' ? ' is-active' : '' ?>" href="<?= e(url('minha-estante.php?status=lendo')) ?>">
        Em leitura (<?= $contagens['lendo'] ?>)
      </a>
      <a class="lp-filter-link<?= $statusFiltro === 'concluido' ? ' is-active' : '' ?>" href="<?= e(url('minha-estante.php?status=concluido')) ?>">
        Concluídos (<?= $contagens['concluido'] ?>)
      </a>
      <a class="lp-filter-link<?= $statusFiltro === 'pausado' ? ' is-active' : '' ?>" href="<?= e(url('minha-estante.php?status=pausado')) ?>">
        Pausados (<?= $contagens['pausado'] ?>)
      </a>
    </nav>
  </div>
</section>

<!-- lista de leituras -->
<section class="lp-band-ivory">
  <div class="lp-container">
    <?php if ($leituras === []): ?>
      <?php View::component('empty-state', [
          'titulo' => $statusFiltro !== '' ? 'Nenhuma leitura nesta categoria' : 'Sua estante ainda está vazia',
          'mensagem' => $statusFiltro !== ''
              ? 'Você não possui leituras marcadas com esta situação no momento.'
              : 'Entre em um clube com ciclo em andamento e registre seu progresso no Kindle ou no livro físico para vê-lo aqui.',
          'ctaTexto' => $statusFiltro !== '' ? 'Ver todas as leituras' : 'Ir para Meus clubes',
          'ctaUrl' => $statusFiltro !== '' ? 'minha-estante.php' : 'meus-clubes.php',
      ]); ?>
    <?php else: ?>
      <div class="row g-5">
        <?php foreach ($leituras as $idx => $item): ?>
          <?php
            $pct = (float) $item['progresso_pct'];
            $idCiclo = (int) $item['id_ciclo'];
            $formatoDesc = $item['formato'] === 'kindle'
                ? 'Kindle'
                : ('Físico' . (!empty($item['editora']) ? ' · ' . $item['editora'] : '') . ' (pág. ' . (int) $item['pagina_atual'] . '/' . (int) $item['total_paginas'] . ')');
          ?>
          <div class="col-lg-6">
            <article class="lp-club-entry">
              <div class="row g-4 align-items-start">
                <div class="col-4 col-sm-3">
                  <a href="<?= e(url('livro.php?id=' . (int) $item['id_livro'])) ?>">
                    <?php View::component('book-cover', [
                        'titulo' => (string) $item['livro_titulo'],
                        'autor' => (string) $item['livro_autor'],
                        'ano' => $item['livro_ano'] ?? '',
                        'capaUrl' => (string) ($item['livro_capa'] ?? ''),
                        'tomIndex' => $idx,
                    ]); ?>
                  </a>
                </div>

                <div class="col-8 col-sm-9">
                  <p class="lp-meta-line small mb-1">
                    <a class="text-decoration-none" href="<?= e(url('clube.php?id=' . (int) $item['id_clube'])) ?>">
                      <?= e((string) $item['clube_nome']) ?>
                    </a>
                    &mdash; <?= e($rotuloStatus((string) $item['status_leitura'])) ?>
                  </p>

                  <h2 class="h3 mb-1">
                    <a class="lp-entry-title-link" href="<?= e(url('ciclo.php?id=' . $idCiclo)) ?>">
                      <?= e((string) $item['livro_titulo']) ?>
                    </a>
                  </h2>
                  <p class="lp-muted small mb-3"><?= e((string) $item['livro_autor']) ?></p>

                  <div class="d-flex justify-content-between align-items-baseline small mb-1">
                    <span class="lp-muted"><?= e($formatoDesc) ?></span>
                    <strong style="color: var(--lp-moss-deep);"><?= e(formatarPercentual($pct)) ?></strong>
                  </div>
                  <div class="lp-progress-track mb-3">
                    <div class="lp-progress-fill" style="width: <?= number_format($pct, 2, '.', '') ?>%;"></div>
                  </div>

                  <div class="d-flex flex-wrap gap-3 small">
                    <a class="lp-inline-link" href="<?= e(url('ciclo.php?id=' . $idCiclo)) ?>">Sala do ciclo</a>
                    <a class="lp-muted" href="<?= e(url('caderno.php?id=' . $idCiclo)) ?>">Caderno de Margens</a>
                  </div>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
