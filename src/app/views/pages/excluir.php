<?php

declare(strict_types=1);

use App\Core\Csrf;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array|null $alvo
 * @var array $indice
 */
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <span class="lp-section-label">Confirmação segura</span>
    <h1 class="mt-1 mb-2"><?= $alvo !== null ? 'Confirmar ação' : 'Excluir ou arquivar registros' ?></h1>
    <p class="lp-muted mb-0" style="max-width: 58ch;">
      <?= $alvo !== null
          ? 'Confira o registro e as consequências antes de confirmar. Nenhuma exclusão acontece apenas abrindo um link.'
          : 'Escolha um registro sob sua autoria ou responsabilidade para remover com segurança ou arquivar um clube.' ?>
    </p>
  </div>
</section>

<section class="lp-band-ivory">
  <div class="lp-container">
    <?php if ($alvo !== null): ?>
      <div style="max-width: 640px;">
        <p class="lp-meta-line mb-1"><?= e((string) $alvo['contexto']) ?></p>
        <h2 class="mb-3"><?= e((string) $alvo['nome']) ?></h2>

        <?php if (!empty($alvo['bloqueio'])): ?>
          <div class="lp-alert lp-alert-aviso mb-4">
            <div>
              <strong>Ação indisponível:</strong>
              <?= e((string) $alvo['bloqueio']) ?>
            </div>
          </div>
          <a class="btn-lp-secondary" href="<?= e(url((string) $alvo['urlVoltar'])) ?>">Voltar</a>
        <?php else: ?>
          <p class="lp-muted mb-4"><?= e((string) $alvo['consequencia']) ?></p>

          <form method="POST" action="<?= e(url('actions/excluir.php')) ?>" class="d-flex flex-wrap gap-3 align-items-center">
            <?= Csrf::field() ?>
            <input type="hidden" name="tipo" value="<?= e((string) $alvo['tipo']) ?>">
            <input type="hidden" name="id" value="<?= (int) $alvo['id'] ?>">
            <button type="submit" class="btn-lp-danger" data-loading-text="Processando...">
              <?= e((string) $alvo['rotuloBotao']) ?>
            </button>
            <a class="lp-inline-link" href="<?= e(url((string) $alvo['urlVoltar'])) ?>">Cancelar</a>
          </form>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div class="row g-5">
        <div class="col-lg-6">
          <h2 class="h3 mb-3">Arquivar clubes sob sua responsabilidade</h2>
          <?php if ($indice['clubes'] === []): ?>
            <p class="lp-muted small mb-5">Nenhum clube ativo sob sua responsabilidade.</p>
          <?php else: ?>
            <div class="lp-entry-list mb-5">
              <?php foreach ($indice['clubes'] as $c): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block"><?= e((string) $c['nome']) ?></strong>
                    <small class="lp-muted"><?= e((string) $c['tema_nome']) ?></small>
                  </div>
                  <a class="small text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=clube&id=' . (int) $c['id_clube'])) ?>">Arquivar</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h2 class="h3 mb-3">Suas anotações</h2>
          <?php if ($indice['anotacoes'] === []): ?>
            <p class="lp-muted small mb-0">Nenhuma anotação publicada por você.</p>
          <?php else: ?>
            <div class="lp-entry-list">
              <?php foreach ($indice['anotacoes'] as $a): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block">
                      <?= e((string) ($a['titulo'] ?: 'Anotação em ' . formatarPercentual((float) $a['posicao_pct']))) ?>
                    </strong>
                    <small class="lp-muted"><?= e((string) $a['clube_nome']) ?></small>
                  </div>
                  <a class="small text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=anotacao&id=' . (int) $a['id_anotacao'])) ?>">Excluir</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-lg-6 lp-col-divider">
          <h2 class="h3 mb-3">Livros cadastrados por você</h2>
          <?php if ($indice['livros'] === []): ?>
            <p class="lp-muted small mb-5">Nenhum livro cadastrado por você.</p>
          <?php else: ?>
            <div class="lp-entry-list mb-5">
              <?php foreach ($indice['livros'] as $l): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block"><?= e((string) $l['titulo']) ?></strong>
                    <small class="lp-muted"><?= e((string) $l['autor_principal']) ?></small>
                  </div>
                  <a class="small text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=livro&id=' . (int) $l['id_livro'])) ?>">Excluir</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h2 class="h3 mb-3">Edições físicas cadastradas por você</h2>
          <?php if ($indice['edicoes'] === []): ?>
            <p class="lp-muted small mb-0">Nenhuma edição física cadastrada por você.</p>
          <?php else: ?>
            <div class="lp-entry-list">
              <?php foreach ($indice['edicoes'] as $ed): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block"><?= e((string) $ed['livro_titulo']) ?></strong>
                    <small class="lp-muted"><?= e((string) ($ed['editora'] ?: 'Edição física')) ?></small>
                  </div>
                  <a class="small text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=edicao&id=' . (int) $ed['id_edicao'])) ?>">Excluir</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
