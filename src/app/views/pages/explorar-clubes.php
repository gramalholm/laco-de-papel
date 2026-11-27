<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\View;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array $temas
 * @var array $clubes
 * @var string $termo
 * @var int $idTema
 * @var array|null $temaSelecionado
 */

$descreverCiclo = static function (?string $status, ?string $livro): string {
    if ($status === 'em_andamento') {
        return $livro ? 'Lendo ' . $livro : 'Leitura em andamento';
    }
    if ($status === 'votacao') {
        return 'Votação aberta';
    }
    return 'Aberto para novo ciclo';
};

$corSwatch = static function (?string $cor): string {
    return match ($cor) {
        'verde' => 'var(--lp-moss)',
        'madeira' => 'var(--lp-wood)',
        default => 'var(--lp-burgundy)',
    };
};

$temFiltro = $termo !== '' || $idTema > 0;
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
      <div>
        <span class="lp-section-label lp-section-label-moss">Comunidades abertas</span>
        <h1 class="mt-1 mb-2">Encontre seu próximo clube</h1>
        <p class="lp-muted mb-0" style="max-width: 54ch;">
          Clubes públicos organizados por interesse literário. Grupos privados permanecem reservados apenas a convidados.
        </p>
      </div>
      <?php if (Auth::check()): ?>
        <a class="btn-lp-moss" href="<?= e(url('clube-form.php')) ?>">Abrir um clube</a>
      <?php else: ?>
        <a class="btn-lp-secondary" href="<?= e(url('registro.php')) ?>">Criar conta</a>
      <?php endif; ?>
    </div>

    <form method="GET" action="<?= e(url('explorar-clubes.php')) ?>" class="pb-3" style="border-bottom: 1px solid var(--lp-border);">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="lp-field-label-caps" for="filtro-q">Buscar por nome</label>
          <input
            type="search"
            id="filtro-q"
            name="q"
            class="form-control lp-line-input"
            value="<?= e($termo) ?>"
            placeholder="Digite o nome de um clube..."
          >
        </div>

        <div class="col-md-4">
          <label class="lp-field-label-caps" for="filtro-tema">Assunto</label>
          <select id="filtro-tema" name="tema" class="form-select lp-line-input" data-auto-submit="true">
            <option value="">Todos os assuntos</option>
            <?php foreach ($temas as $tema): ?>
              <option value="<?= (int) $tema['id_tema'] ?>"<?= $idTema === (int) $tema['id_tema'] ? ' selected' : '' ?>>
                <?= e((string) $tema['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2 d-flex gap-2 justify-content-md-end">
          <button type="submit" class="btn-lp-primary btn-lp-sm">Filtrar</button>
          <?php if ($temFiltro): ?>
            <a class="btn-lp-secondary btn-lp-sm" href="<?= e(url('explorar-clubes.php')) ?>">Limpar</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php if ($temas !== []): ?>
      <nav class="lp-filter-nav" aria-label="Filtrar clubes por assunto">
        <a
          class="lp-filter-link<?= $idTema === 0 ? ' is-active' : '' ?>"
          href="<?= e(url('explorar-clubes.php' . ($termo !== '' ? '?q=' . rawurlencode($termo) : ''))) ?>"
        >
          Todos
        </a>
        <?php foreach ($temas as $tema): ?>
          <a
            class="lp-filter-link<?= $idTema === (int) $tema['id_tema'] ? ' is-active' : '' ?>"
            href="<?= e(url('explorar-clubes.php?tema=' . (int) $tema['id_tema'] . ($termo !== '' ? '&q=' . rawurlencode($termo) : ''))) ?>"
          >
            <?= e((string) $tema['nome']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
  </div>
</section>

<!-- listagem de clubes -->
<section class="lp-band-ivory">
  <div class="lp-container">
    <p class="lp-meta-line mb-4">
      <?php $total = count($clubes); ?>
      <?= $total ?> <?= $total === 1 ? 'clube público encontrado' : 'clubes públicos encontrados' ?>
      <?php if ($temaSelecionado !== null): ?>
        em <strong><?= e((string) $temaSelecionado['nome']) ?></strong>
      <?php endif; ?>
      <?php if ($termo !== ''): ?>
        para <strong>“<?= e($termo) ?>”</strong>
      <?php endif; ?>
    </p>

    <?php if ($clubes === []): ?>
      <?php View::component('empty-state', [
          'titulo' => 'Nenhum clube encontrado com esses filtros',
          'mensagem' => $temFiltro
              ? 'Limpe os filtros para ver todos os clubes abertos ou crie um novo espaço em torno desse assunto.'
              : 'Ainda não há clubes públicos cadastrados. Você pode abrir o primeiro agora.',
          'ctaTexto' => $temFiltro ? 'Ver todos os clubes' : (Auth::check() ? 'Criar primeiro clube' : 'Criar conta'),
          'ctaUrl' => $temFiltro ? 'explorar-clubes.php' : (Auth::check() ? 'clube-form.php' : 'registro.php'),
      ]); ?>
    <?php else: ?>
      <div class="row g-5">
        <?php foreach ($clubes as $clube): ?>
          <?php
            $totalMembros = (int) $clube['total_membros'];
            $lendoAgora = ($clube['ciclo_ativo_status'] ?? null) === 'em_andamento';
          ?>
          <div class="col-md-6">
            <article class="lp-club-entry">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-2 lp-meta-line">
                <span class="d-inline-flex align-items-center gap-2">
                  <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color: <?= $corSwatch($clube['cor_identidade'] ?? null) ?>;" aria-hidden="true"></span>
                  <?= e((string) $clube['tema_nome']) ?>
                </span>
                <span><?= $totalMembros ?> <?= $totalMembros === 1 ? 'leitor' : 'leitores' ?></span>
              </div>

              <h2 class="h3 mb-2">
                <a class="lp-entry-title-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">
                  <?= e((string) $clube['nome']) ?>
                </a>
              </h2>

              <p class="lp-muted small mb-4 flex-grow-1">
                <?= e(mb_strimwidth((string) $clube['descricao'], 0, 170, '…', 'UTF-8')) ?>
              </p>

              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="lp-club-status<?= $lendoAgora ? ' is-reading' : '' ?>">
                  <?= e($descreverCiclo($clube['ciclo_ativo_status'] ?? null, $clube['ciclo_ativo_livro'] ?? null)) ?>
                </span>
                <a class="lp-inline-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">
                  Conhecer clube
                </a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
