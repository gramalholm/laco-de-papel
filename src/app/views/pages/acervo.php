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
 * @var string $termo
 * @var array $livros
 * @var bool $abrirCadastro
 */

$incluirEdicao = old('incluir_edicao') === '1'
    || field_error('editora') !== null
    || field_error('total_paginas') !== null
    || field_error('isbn13') !== null
    || field_error('isbn10') !== null;
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
      <div>
        <span class="lp-section-label">Catálogo compartilhado</span>
        <h1 class="mt-1 mb-2">Acervo de livros</h1>
        <p class="lp-muted mb-0" style="max-width: 56ch;">
          Obras cadastradas pelos leitores com suas edições físicas e contagem de páginas para acompanhar cada leitura.
        </p>
      </div>

      <?php if ($usuario !== null): ?>
        <button
          type="button"
          class="btn-lp-moss"
          data-bs-toggle="collapse"
          data-bs-target="#painelCadastroLivro"
          aria-expanded="<?= $abrirCadastro ? 'true' : 'false' ?>"
          aria-controls="painelCadastroLivro"
        >
          Adicionar livro
        </button>
      <?php else: ?>
        <a class="btn-lp-secondary" href="<?= e(url('login.php')) ?>">Entrar para cadastrar</a>
      <?php endif; ?>
    </div>

    <form method="GET" action="<?= e(url('acervo.php')) ?>">
      <div class="row g-3 align-items-end">
        <div class="col-md-9">
          <label class="lp-field-label-caps" for="busca-acervo">Pesquisar por título ou autor</label>
          <input
            type="search"
            id="busca-acervo"
            name="q"
            class="form-control lp-line-input"
            value="<?= e($termo) ?>"
            placeholder="Ex.: Dom Casmurro, Machado de Assis, Mary Shelley..."
          >
        </div>
        <div class="col-md-3 d-flex gap-2 justify-content-md-end">
          <button type="submit" class="btn-lp-primary btn-lp-sm">Pesquisar</button>
          <?php if ($termo !== ''): ?>
            <a class="btn-lp-secondary btn-lp-sm" href="<?= e(url('acervo.php')) ?>">Limpar</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php if ($usuario !== null): ?>
      <div class="collapse<?= $abrirCadastro ? ' show' : '' ?>" id="painelCadastroLivro">
        <div class="mt-5 pt-5" style="border-top: 1px solid var(--lp-border-strong);">
          <div class="row g-5">
            <div class="col-lg-4">
              <span class="lp-section-label lp-section-label-moss mb-2">Cadastro manual</span>
              <h2 class="h3 mb-2">Incluir obra no acervo</h2>
              <p class="lp-muted small mb-0">
                Qualquer livro cadastrado aqui fica disponível para indicação e votação em todos os clubes. Se quiser, já informe os dados da sua edição física.
              </p>
            </div>

            <div class="col-lg-7 offset-lg-1 lp-col-divider">
              <form method="POST" action="<?= e(url('actions/livros/salvar.php')) ?>" novalidate>
                <?= Csrf::field() ?>

                <div class="row g-4 mb-4">
                  <div class="col-md-7">
                    <label class="lp-field-label-caps" for="titulo">Título da obra</label>
                    <input
                      type="text"
                      id="titulo"
                      name="titulo"
                      class="form-control lp-line-input<?= field_error('titulo') ? ' is-invalid' : '' ?>"
                      value="<?= e(old('titulo')) ?>"
                      placeholder="Ex.: Grande Sertão: Veredas"
                      maxlength="250"
                      required
                    >
                    <?php if (field_error('titulo')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('titulo')) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="col-md-5">
                    <label class="lp-field-label-caps" for="autor_principal">Autor principal</label>
                    <input
                      type="text"
                      id="autor_principal"
                      name="autor_principal"
                      class="form-control lp-line-input<?= field_error('autor_principal') ? ' is-invalid' : '' ?>"
                      value="<?= e(old('autor_principal')) ?>"
                      placeholder="Ex.: João Guimarães Rosa"
                      maxlength="180"
                      required
                    >
                    <?php if (field_error('autor_principal')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('autor_principal')) ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="row g-4 mb-4">
                  <div class="col-sm-4 col-md-3">
                    <label class="lp-field-label-caps" for="ano_primeira_publicacao">Ano (opcional)</label>
                    <input
                      type="number"
                      id="ano_primeira_publicacao"
                      name="ano_primeira_publicacao"
                      class="form-control lp-line-input<?= field_error('ano_primeira_publicacao') ? ' is-invalid' : '' ?>"
                      value="<?= e(old('ano_primeira_publicacao')) ?>"
                      placeholder="1956"
                      min="1000"
                      max="2100"
                    >
                    <?php if (field_error('ano_primeira_publicacao')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('ano_primeira_publicacao')) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="col-sm-4 col-md-3">
                    <label class="lp-field-label-caps" for="idioma_original">Idioma</label>
                    <input
                      type="text"
                      id="idioma_original"
                      name="idioma_original"
                      class="form-control lp-line-input<?= field_error('idioma_original') ? ' is-invalid' : '' ?>"
                      value="<?= e(old('idioma_original', 'pt-BR')) ?>"
                      placeholder="pt-BR"
                      maxlength="20"
                    >
                    <?php if (field_error('idioma_original')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('idioma_original')) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="col-sm-4 col-md-6">
                    <label class="lp-field-label-caps" for="capa_url">URL da capa HTTPS (opcional)</label>
                    <input
                      type="url"
                      id="capa_url"
                      name="capa_url"
                      class="form-control lp-line-input<?= field_error('capa_url') ? ' is-invalid' : '' ?>"
                      value="<?= e(old('capa_url')) ?>"
                      placeholder="https://..."
                      maxlength="700"
                    >
                    <?php if (field_error('capa_url')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('capa_url')) ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="lp-field-label-caps" for="descricao">Sinopse (opcional)</label>
                  <textarea
                    id="descricao"
                    name="descricao"
                    class="form-control lp-line-input<?= field_error('descricao') ? ' is-invalid' : '' ?>"
                    rows="2"
                    maxlength="3000"
                    placeholder="Breve apresentação da obra sem revelar o desfecho."
                  ><?= e(old('descricao')) ?></textarea>
                  <?php if (field_error('descricao')): ?>
                    <div class="lp-field-error"><?= e((string) field_error('descricao')) ?></div>
                  <?php endif; ?>
                </div>

                <div class="pt-3 mb-4" style="border-top: 1px solid var(--lp-border);">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      id="incluir_edicao"
                      name="incluir_edicao"
                      value="1"
                      class="form-check-input"
                      <?= $incluirEdicao ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="incluir_edicao">
                      Já incluir uma edição física (editora, páginas e ISBN)
                    </label>
                  </div>

                  <div id="bloco-primeira-edicao" class="row g-4 mt-2<?= $incluirEdicao ? '' : ' d-none' ?>">
                    <div class="col-md-5">
                      <label class="lp-field-label-caps" for="editora">Editora</label>
                      <input type="text" id="editora" name="editora" class="form-control lp-line-input<?= field_error('editora') ? ' is-invalid' : '' ?>" value="<?= e(old('editora')) ?>" placeholder="Companhia das Letras" maxlength="180">
                      <?php if (field_error('editora')): ?>
                        <div class="lp-field-error"><?= e((string) field_error('editora')) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="col-sm-6 col-md-3">
                      <label class="lp-field-label-caps" for="total_paginas">Páginas</label>
                      <input
                        type="number"
                        id="total_paginas"
                        name="total_paginas"
                        class="form-control lp-line-input<?= field_error('total_paginas') ? ' is-invalid' : '' ?>"
                        value="<?= e(old('total_paginas')) ?>"
                        placeholder="256"
                        min="1"
                        max="10000"
                      >
                      <?php if (field_error('total_paginas')): ?>
                        <div class="lp-field-error"><?= e((string) field_error('total_paginas')) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="col-sm-6 col-md-4">
                      <label class="lp-field-label-caps" for="isbn13">ISBN-13 (opcional)</label>
                      <input
                        type="text"
                        id="isbn13"
                        name="isbn13"
                        class="form-control lp-line-input<?= field_error('isbn13') ? ' is-invalid' : '' ?>"
                        value="<?= e(old('isbn13')) ?>"
                        placeholder="9788535914849"
                        maxlength="20"
                        data-clean-isbn="true"
                      >
                      <?php if (field_error('isbn13')): ?>
                        <div class="lp-field-error"><?= e((string) field_error('isbn13')) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center">
                  <button type="submit" class="btn-lp-moss btn-lp-sm" data-loading-text="Salvando...">Salvar no acervo</button>
                  <button type="button" class="btn btn-link btn-sm p-0 lp-muted" data-bs-toggle="collapse" data-bs-target="#painelCadastroLivro">Fechar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- listagem do acervo -->
<section class="lp-band-ivory">
  <div class="lp-container">
    <p class="lp-meta-line mb-4">
      <?php $total = count($livros); ?>
      <?= $total ?> <?= $total === 1 ? 'obra no acervo' : 'obras no acervo' ?>
      <?php if ($termo !== ''): ?>
        para <strong>“<?= e($termo) ?>”</strong>
      <?php endif; ?>
    </p>

    <?php if ($livros === []): ?>
      <?php View::component('empty-state', [
          'titulo' => 'Nenhuma obra encontrada',
          'mensagem' => $termo !== ''
              ? 'Tente outro termo de busca ou cadastre este título manualmente.'
              : 'O acervo ainda está vazio. Cadastre o primeiro livro para iniciar leituras nos clubes.',
          'ctaTexto' => $termo !== '' ? 'Ver todo o acervo' : ($usuario !== null ? 'Adicionar livro' : 'Entrar'),
          'ctaUrl' => $termo !== '' ? 'acervo.php' : ($usuario !== null ? 'acervo.php?novo=1' : 'login.php'),
      ]); ?>
    <?php else: ?>
      <div class="row g-4 g-lg-5">
        <?php foreach ($livros as $idx => $livro): ?>
          <?php $qtdEdicoes = (int) ($livro['total_edicoes'] ?? 0); ?>
          <div class="col-6 col-md-4 col-lg-3">
            <a class="lp-book-shelf-item" href="<?= e(url('livro.php?id=' . (int) $livro['id_livro'])) ?>">
              <?php View::component('book-cover', [
                  'titulo' => (string) $livro['titulo'],
                  'autor' => (string) $livro['autor_principal'],
                  'ano' => $livro['ano_primeira_publicacao'] ?? '',
                  'capaUrl' => (string) ($livro['capa_exibicao'] ?? ''),
                  'tomIndex' => $idx,
              ]); ?>
              <div class="lp-book-shelf-title"><?= e((string) $livro['titulo']) ?></div>
              <div class="lp-book-shelf-meta">
                <?= e((string) $livro['autor_principal']) ?>
                <?php if (!empty($livro['ano_primeira_publicacao'])): ?>
                  &middot; <?= (int) $livro['ano_primeira_publicacao'] ?>
                <?php endif; ?>
              </div>
              <div class="lp-meta-line small">
                <?= $qtdEdicoes ?> <?= $qtdEdicoes === 1 ? 'edição física' : 'edições físicas' ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
