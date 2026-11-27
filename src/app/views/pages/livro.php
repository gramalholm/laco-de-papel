<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array|null $usuario
 * @var array $livro
 * @var string $capaExibicao
 * @var array $edicoes
 * @var array $ciclosVisiveis
 * @var bool $podeGerenciarLivro
 */

$idLivro = (int) $livro['id_livro'];
$rotuloStatusCiclo = static function (string $status): string {
    return match ($status) {
        'votacao' => 'Em votação',
        'em_andamento' => 'Em leitura',
        'encerrado' => 'Concluído',
        default => $status,
    };
};

$metadadosInline = [];
if (!empty($livro['ano_primeira_publicacao'])) {
    $metadadosInline[] = (string) (int) $livro['ano_primeira_publicacao'];
}
if (!empty($livro['idioma_original'])) {
    $metadadosInline[] = (string) $livro['idioma_original'];
}
if (!empty($livro['criado_por_apelido'])) {
    $metadadosInline[] = 'cadastrado por @' . (string) $livro['criado_por_apelido'];
}
?>
<article>
  <section class="lp-page-intro pb-5">
    <div class="lp-container">
      <p class="mb-4">
        <a class="lp-inline-link" href="<?= e(url('acervo.php')) ?>">&larr; Acervo</a>
      </p>

      <div class="row g-5 align-items-start">
        <div class="col-md-4 col-lg-3">
          <div class="lp-book-detail-cover">
            <?php View::component('book-cover', [
                'titulo' => (string) $livro['titulo'],
                'autor' => (string) $livro['autor_principal'],
                'ano' => $livro['ano_primeira_publicacao'] ?? '',
                'capaUrl' => $capaExibicao,
            ]); ?>
          </div>
        </div>

        <div class="col-md-8 col-lg-8 offset-lg-1">
          <span class="lp-section-label lp-section-label-moss mb-2"><?= e((string) $livro['autor_principal']) ?></span>
          <h1 class="mb-3"><?= e((string) $livro['titulo']) ?></h1>

          <?php if ($metadadosInline !== []): ?>
            <p class="lp-meta-line mb-4">
              <?= e(implode(' — ', $metadadosInline)) ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($livro['descricao'])): ?>
            <p class="lp-editorial-prose mb-4" style="white-space: pre-line;">
              <?= e((string) $livro['descricao']) ?>
            </p>
          <?php else: ?>
            <p class="lp-muted small mb-4">Sem sinopse cadastrada para esta obra.</p>
          <?php endif; ?>

          <?php if ($podeGerenciarLivro): ?>
            <div class="d-flex flex-wrap gap-3 pt-3" style="border-top: 1px solid var(--lp-border);">
              <a class="lp-inline-link" href="<?= e(url('atualizar.php?tipo=livro&id=' . $idLivro)) ?>">
                Editar obra
              </a>
              <a class="small text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=livro&id=' . $idLivro)) ?>">
                Excluir obra
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- edições e clubes -->
  <section class="lp-band-ivory">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-7">
          <div class="mb-5" aria-labelledby="sec-edicoes">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
              <h2 id="sec-edicoes" class="h3 mb-0">Edições físicas</h2>
              <span class="lp-meta-line"><?= count($edicoes) ?> <?= count($edicoes) === 1 ? 'edição' : 'edições' ?></span>
            </div>
            <p class="lp-muted small mb-4">
              A contagem de páginas de cada edição permite converter o avanço no livro impresso para porcentagem.
            </p>

            <?php if ($edicoes === []): ?>
              <p class="lp-muted small mb-0">
                Nenhuma edição física cadastrada ainda. Leitores de Kindle já podem acompanhar a obra em porcentagem.
              </p>
            <?php else: ?>
              <div class="lp-entry-list">
                <?php foreach ($edicoes as $ed): ?>
                  <?php
                    $podeGerenciarEdicao = (
                        $usuario !== null
                        && $ed['criado_por'] !== null
                        && (int) $ed['criado_por'] === $usuario['id_usuario']
                    );
                    $detalhes = [];
                    if (!empty($ed['editora'])) {
                        $detalhes[] = (string) $ed['editora'];
                    }
                    if (!empty($ed['ano_publicacao'])) {
                        $detalhes[] = (string) (int) $ed['ano_publicacao'];
                    }
                    if (!empty($ed['idioma'])) {
                        $detalhes[] = (string) $ed['idioma'];
                    }
                    if (!empty($ed['isbn13'])) {
                        $detalhes[] = 'ISBN ' . (string) $ed['isbn13'];
                    } elseif (!empty($ed['isbn10'])) {
                        $detalhes[] = 'ISBN ' . (string) $ed['isbn10'];
                    }
                  ?>
                  <div class="lp-entry-row-compact">
                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                      <div>
                        <strong class="d-block">
                          <?= e((string) ($ed['titulo_edicao'] ?: ($ed['editora'] ?: 'Edição #' . (int) $ed['id_edicao']))) ?>
                        </strong>
                        <?php if ($detalhes !== []): ?>
                          <small class="lp-muted"><?= e(implode(' — ', $detalhes)) ?></small>
                        <?php endif; ?>
                      </div>

                      <div class="text-md-end">
                        <?php if (!empty($ed['total_paginas'])): ?>
                          <span class="fw-semibold" style="color: var(--lp-moss-deep); font-size: 0.92rem;">
                            <?= (int) $ed['total_paginas'] ?> páginas
                          </span>
                        <?php else: ?>
                          <small class="lp-muted">Páginas não informadas</small>
                        <?php endif; ?>
                      </div>
                    </div>

                    <?php if ($podeGerenciarEdicao): ?>
                      <div class="d-flex gap-3 mt-2 small">
                        <a href="<?= e(url('atualizar.php?tipo=edicao&id=' . (int) $ed['id_edicao'])) ?>">Editar edição</a>
                        <a class="text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=edicao&id=' . (int) $ed['id_edicao'])) ?>">Excluir</a>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($usuario !== null): ?>
            <div class="pt-4" style="border-top: 1px solid var(--lp-border-strong);">
              <h3 class="h4 mb-1">Adicionar edição física</h3>
              <p class="lp-muted small mb-4">
                Está lendo em outra editora? Registre aqui o total de páginas da sua cópia.
              </p>

              <form method="POST" action="<?= e(url('actions/livros/salvar-edicao.php')) ?>" novalidate>
                <?= Csrf::field() ?>
                <input type="hidden" name="id_livro" value="<?= $idLivro ?>">

                <div class="row g-4 mb-4">
                  <div class="col-md-6">
                    <label class="lp-field-label-caps" for="ed_titulo">Nome da edição (opcional)</label>
                    <input type="text" id="ed_titulo" name="titulo_edicao" class="form-control lp-line-input<?= field_error('titulo_edicao') ? ' is-invalid' : '' ?>" value="<?= e(old('titulo_edicao')) ?>" placeholder="Ex.: Edição de Bolso" maxlength="250">
                    <?php if (field_error('titulo_edicao')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('titulo_edicao')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-6">
                    <label class="lp-field-label-caps" for="ed_editora">Editora</label>
                    <input type="text" id="ed_editora" name="editora" class="form-control lp-line-input<?= field_error('editora') ? ' is-invalid' : '' ?>" value="<?= e(old('editora')) ?>" placeholder="Ex.: Penguin-Companhia" maxlength="180" required>
                    <?php if (field_error('editora')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('editora')) ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="row g-4 mb-4">
                  <div class="col-sm-4">
                    <label class="lp-field-label-caps" for="ed_paginas">Total de páginas</label>
                    <input type="number" id="ed_paginas" name="total_paginas" class="form-control lp-line-input<?= field_error('total_paginas') ? ' is-invalid' : '' ?>" value="<?= e(old('total_paginas')) ?>" placeholder="256" min="1" max="10000" required>
                    <?php if (field_error('total_paginas')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('total_paginas')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="col-sm-4">
                    <label class="lp-field-label-caps" for="ed_ano">Ano (opcional)</label>
                    <input type="number" id="ed_ano" name="ano_publicacao" class="form-control lp-line-input<?= field_error('ano_publicacao') ? ' is-invalid' : '' ?>" value="<?= e(old('ano_publicacao')) ?>" placeholder="2020" min="1450" max="2100">
                    <?php if (field_error('ano_publicacao')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('ano_publicacao')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="col-sm-4">
                    <label class="lp-field-label-caps" for="ed_idioma">Idioma</label>
                    <input type="text" id="ed_idioma" name="idioma" class="form-control lp-line-input<?= field_error('idioma') ? ' is-invalid' : '' ?>" value="<?= e(old('idioma', 'pt-BR')) ?>" placeholder="pt-BR" maxlength="20">
                    <?php if (field_error('idioma')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('idioma')) ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="row g-4 mb-4">
                  <div class="col-sm-6">
                    <label class="lp-field-label-caps" for="ed_isbn13">ISBN-13 (opcional)</label>
                    <input type="text" id="ed_isbn13" name="isbn13" class="form-control lp-line-input<?= field_error('isbn13') ? ' is-invalid' : '' ?>" value="<?= e(old('isbn13')) ?>" placeholder="9788582850350" maxlength="20" data-clean-isbn="true">
                    <?php if (field_error('isbn13')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('isbn13')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="col-sm-6">
                    <label class="lp-field-label-caps" for="ed_isbn10">ISBN-10 (opcional)</label>
                    <input type="text" id="ed_isbn10" name="isbn10" class="form-control lp-line-input<?= field_error('isbn10') ? ' is-invalid' : '' ?>" value="<?= e(old('isbn10')) ?>" placeholder="8582850352" maxlength="16" data-clean-isbn="true">
                    <?php if (field_error('isbn10')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('isbn10')) ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="lp-field-label-caps" for="ed_capa_url">URL da capa HTTPS (opcional)</label>
                  <input type="url" id="ed_capa_url" name="capa_url" class="form-control lp-line-input<?= field_error('capa_url') ? ' is-invalid' : '' ?>" value="<?= e(old('capa_url')) ?>" placeholder="https://..." maxlength="700">
                  <?php if (field_error('capa_url')): ?>
                    <div class="lp-field-error"><?= e((string) field_error('capa_url')) ?></div>
                  <?php endif; ?>
                </div>

                <button type="submit" class="btn-lp-moss btn-lp-sm" data-loading-text="Salvando...">
                  Cadastrar edição
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>

        <aside class="col-lg-4 offset-lg-1 lp-col-divider">
          <h2 class="h3 mb-2">Clubes com esta leitura</h2>
          <p class="lp-muted small mb-4">
            Comunidades abertas (e grupos de que você participa) que escolheram esta obra em um ciclo.
          </p>

          <?php if ($ciclosVisiveis === []): ?>
            <p class="lp-meta-line small mb-0">
              Nenhum clube público leu esta obra ainda. Você pode indicá-la na próxima votação do seu grupo.
            </p>
          <?php else: ?>
            <ul class="list-unstyled lp-entry-list mb-0">
              <?php foreach ($ciclosVisiveis as $cv): ?>
                <li class="lp-entry-row-compact">
                  <a class="d-block fw-semibold text-decoration-none" href="<?= e(url('clube.php?id=' . (int) $cv['id_clube'])) ?>">
                    <?= e((string) $cv['clube_nome']) ?>
                  </a>
                  <small class="lp-muted">
                    <?= e($rotuloStatusCiclo((string) $cv['status'])) ?>
                    <?php if (!empty($cv['titulo_ciclo'])): ?>
                      &mdash; <?= e((string) $cv['titulo_ciclo']) ?>
                    <?php endif; ?>
                  </small>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </section>
</article>
