<?php

declare(strict_types=1);

use App\Core\Csrf;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array $usuario
 * @var string $tipo
 * @var array|null $registro
 * @var array $extras
 * @var array $indice
 * @var string $tituloPagina
 */

$rotulosTipo = [
    'grifo' => 'Grifo',
    'duvida' => 'Dúvida',
    'teoria' => 'Teoria',
    'reflexao' => 'Reflexão',
    'comentario' => 'Comentário',
];
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <span class="lp-section-label lp-section-label-moss">Edição contextual</span>
    <h1 class="mt-1 mb-2"><?= e($tituloPagina) ?></h1>
    <p class="lp-muted mb-0" style="max-width: 56ch;">
      <?= $registro !== null
          ? 'Revise e salve as alterações abaixo. Somente registros sob sua autoria ou responsabilidade podem ser editados.'
          : 'Escolha abaixo um clube, anotação, livro ou edição sob sua responsabilidade para atualizar.' ?>
    </p>
  </div>
</section>

<section class="lp-band-ivory">
  <div class="lp-container">
    <?php if ($registro === null): ?>
      <!-- índice geral -->
      <div class="row g-5">
        <div class="col-lg-6">
          <h2 class="h3 mb-3">Clubes que você organiza</h2>
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
                  <a class="lp-inline-link" href="<?= e(url('clube-form.php?id=' . (int) $c['id_clube'])) ?>">Editar</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h2 class="h3 mb-3">Suas anotações</h2>
          <?php if ($indice['anotacoes'] === []): ?>
            <p class="lp-muted small mb-0">Você ainda não publicou anotações no Caderno de Margens.</p>
          <?php else: ?>
            <div class="lp-entry-list">
              <?php foreach ($indice['anotacoes'] as $a): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block">
                      <?= e((string) ($a['titulo'] ?: ($rotulosTipo[$a['tipo']] ?? $a['tipo']) . ' em ' . formatarPercentual((float) $a['posicao_pct']))) ?>
                    </strong>
                    <small class="lp-muted">
                      <?= e((string) $a['clube_nome']) ?>
                      <?php if (!empty($a['livro_titulo'])): ?>
                        &mdash; <?= e((string) $a['livro_titulo']) ?>
                      <?php endif; ?>
                    </small>
                  </div>
                  <a class="lp-inline-link" href="<?= e(url('atualizar.php?tipo=anotacao&id=' . (int) $a['id_anotacao'])) ?>">Editar</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-lg-6 lp-col-divider">
          <h2 class="h3 mb-3">Livros cadastrados por você</h2>
          <?php if ($indice['livros'] === []): ?>
            <p class="lp-muted small mb-5">Nenhum livro cadastrado por você no acervo.</p>
          <?php else: ?>
            <div class="lp-entry-list mb-5">
              <?php foreach ($indice['livros'] as $l): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block"><?= e((string) $l['titulo']) ?></strong>
                    <small class="lp-muted"><?= e((string) $l['autor_principal']) ?></small>
                  </div>
                  <a class="lp-inline-link" href="<?= e(url('atualizar.php?tipo=livro&id=' . (int) $l['id_livro'])) ?>">Editar</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h2 class="h3 mb-3">Edições físicas cadastradas por você</h2>
          <?php if ($indice['edicoes'] === []): ?>
            <p class="lp-muted small mb-0">Nenhuma edição cadastrada por você.</p>
          <?php else: ?>
            <div class="lp-entry-list">
              <?php foreach ($indice['edicoes'] as $ed): ?>
                <div class="lp-entry-row-compact d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <strong class="d-block"><?= e((string) $ed['livro_titulo']) ?></strong>
                    <small class="lp-muted">
                      <?= e((string) ($ed['editora'] ?: 'Edição física')) ?>
                      <?php if (!empty($ed['total_paginas'])): ?>
                        &mdash; <?= (int) $ed['total_paginas'] ?> pág.
                      <?php endif; ?>
                    </small>
                  </div>
                  <a class="lp-inline-link" href="<?= e(url('atualizar.php?tipo=edicao&id=' . (int) $ed['id_edicao'])) ?>">Editar</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($tipo === 'livro'): ?>
      <form method="POST" action="<?= e(url('actions/livros/salvar.php')) ?>" style="max-width: 720px;" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="id_livro" value="<?= (int) $registro['id_livro'] ?>">

        <div class="row g-4 mb-4">
          <div class="col-md-7">
            <label class="lp-field-label-caps" for="titulo">Título da obra</label>
            <input type="text" id="titulo" name="titulo" class="form-control lp-line-input<?= field_error('titulo') ? ' is-invalid' : '' ?>" value="<?= e(old('titulo', (string) $registro['titulo'])) ?>" maxlength="250" required>
            <?php if (field_error('titulo')): ?><div class="lp-field-error"><?= e((string) field_error('titulo')) ?></div><?php endif; ?>
          </div>
          <div class="col-md-5">
            <label class="lp-field-label-caps" for="autor_principal">Autor principal</label>
            <input type="text" id="autor_principal" name="autor_principal" class="form-control lp-line-input<?= field_error('autor_principal') ? ' is-invalid' : '' ?>" value="<?= e(old('autor_principal', (string) $registro['autor_principal'])) ?>" maxlength="180" required>
            <?php if (field_error('autor_principal')): ?><div class="lp-field-error"><?= e((string) field_error('autor_principal')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="ano_primeira_publicacao">Ano original</label>
            <input type="number" id="ano_primeira_publicacao" name="ano_primeira_publicacao" class="form-control lp-line-input<?= field_error('ano_primeira_publicacao') ? ' is-invalid' : '' ?>" value="<?= e(old('ano_primeira_publicacao', (string) ($registro['ano_primeira_publicacao'] ?? ''))) ?>" min="1000" max="2100">
            <?php if (field_error('ano_primeira_publicacao')): ?><div class="lp-field-error"><?= e((string) field_error('ano_primeira_publicacao')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="idioma_original">Idioma</label>
            <input type="text" id="idioma_original" name="idioma_original" class="form-control lp-line-input<?= field_error('idioma_original') ? ' is-invalid' : '' ?>" value="<?= e(old('idioma_original', (string) ($registro['idioma_original'] ?? ''))) ?>" maxlength="20">
            <?php if (field_error('idioma_original')): ?><div class="lp-field-error"><?= e((string) field_error('idioma_original')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="capa_url">URL da capa HTTPS</label>
            <input type="url" id="capa_url" name="capa_url" class="form-control lp-line-input<?= field_error('capa_url') ? ' is-invalid' : '' ?>" value="<?= e(old('capa_url', (string) ($registro['capa_url'] ?? ''))) ?>" maxlength="700">
            <?php if (field_error('capa_url')): ?><div class="lp-field-error"><?= e((string) field_error('capa_url')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="mb-5">
          <label class="lp-field-label-caps" for="descricao">Sinopse</label>
          <textarea id="descricao" name="descricao" class="form-control lp-line-input<?= field_error('descricao') ? ' is-invalid' : '' ?>" rows="4" maxlength="3000"><?= e(old('descricao', (string) ($registro['descricao'] ?? ''))) ?></textarea>
          <?php if (field_error('descricao')): ?><div class="lp-field-error"><?= e((string) field_error('descricao')) ?></div><?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-3 align-items-center">
          <button type="submit" class="btn-lp-moss" data-loading-text="Salvando...">Salvar alterações</button>
          <a class="lp-inline-link" href="<?= e(url('livro.php?id=' . (int) $registro['id_livro'])) ?>">Cancelar</a>
        </div>
      </form>

    <?php elseif ($tipo === 'edicao'): ?>
      <form method="POST" action="<?= e(url('actions/livros/salvar-edicao.php')) ?>" style="max-width: 720px;" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="id_edicao" value="<?= (int) $registro['id_edicao'] ?>">
        <input type="hidden" name="id_livro" value="<?= (int) $registro['id_livro'] ?>">

        <p class="lp-meta-line mb-4">Obra: <strong><?= e((string) $registro['livro_titulo']) ?></strong></p>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <label class="lp-field-label-caps" for="titulo_edicao">Nome da edição</label>
            <input type="text" id="titulo_edicao" name="titulo_edicao" class="form-control lp-line-input<?= field_error('titulo_edicao') ? ' is-invalid' : '' ?>" value="<?= e(old('titulo_edicao', (string) ($registro['titulo_edicao'] ?? ''))) ?>" maxlength="250">
            <?php if (field_error('titulo_edicao')): ?><div class="lp-field-error"><?= e((string) field_error('titulo_edicao')) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="lp-field-label-caps" for="editora">Editora</label>
            <input type="text" id="editora" name="editora" class="form-control lp-line-input<?= field_error('editora') ? ' is-invalid' : '' ?>" value="<?= e(old('editora', (string) ($registro['editora'] ?? ''))) ?>" maxlength="180" required>
            <?php if (field_error('editora')): ?><div class="lp-field-error"><?= e((string) field_error('editora')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="total_paginas">Total de páginas</label>
            <input type="number" id="total_paginas" name="total_paginas" class="form-control lp-line-input<?= field_error('total_paginas') ? ' is-invalid' : '' ?>" value="<?= e(old('total_paginas', (string) ($registro['total_paginas'] ?? ''))) ?>" min="1" max="10000" required>
            <?php if (field_error('total_paginas')): ?><div class="lp-field-error"><?= e((string) field_error('total_paginas')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="ano_publicacao">Ano</label>
            <input type="number" id="ano_publicacao" name="ano_publicacao" class="form-control lp-line-input<?= field_error('ano_publicacao') ? ' is-invalid' : '' ?>" value="<?= e(old('ano_publicacao', (string) ($registro['ano_publicacao'] ?? ''))) ?>" min="1450" max="2100">
            <?php if (field_error('ano_publicacao')): ?><div class="lp-field-error"><?= e((string) field_error('ano_publicacao')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="idioma">Idioma</label>
            <input type="text" id="idioma" name="idioma" class="form-control lp-line-input<?= field_error('idioma') ? ' is-invalid' : '' ?>" value="<?= e(old('idioma', (string) ($registro['idioma'] ?? ''))) ?>" maxlength="20">
            <?php if (field_error('idioma')): ?><div class="lp-field-error"><?= e((string) field_error('idioma')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-sm-6">
            <label class="lp-field-label-caps" for="isbn13">ISBN-13</label>
            <input type="text" id="isbn13" name="isbn13" class="form-control lp-line-input<?= field_error('isbn13') ? ' is-invalid' : '' ?>" value="<?= e(old('isbn13', (string) ($registro['isbn13'] ?? ''))) ?>" maxlength="20" data-clean-isbn="true">
            <?php if (field_error('isbn13')): ?><div class="lp-field-error"><?= e((string) field_error('isbn13')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-6">
            <label class="lp-field-label-caps" for="isbn10">ISBN-10</label>
            <input type="text" id="isbn10" name="isbn10" class="form-control lp-line-input<?= field_error('isbn10') ? ' is-invalid' : '' ?>" value="<?= e(old('isbn10', (string) ($registro['isbn10'] ?? ''))) ?>" maxlength="16" data-clean-isbn="true">
            <?php if (field_error('isbn10')): ?><div class="lp-field-error"><?= e((string) field_error('isbn10')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="mb-5">
          <label class="lp-field-label-caps" for="capa_url">URL da capa HTTPS (opcional)</label>
          <input type="url" id="capa_url" name="capa_url" class="form-control lp-line-input<?= field_error('capa_url') ? ' is-invalid' : '' ?>" value="<?= e(old('capa_url', (string) ($registro['capa_url'] ?? ''))) ?>" maxlength="700" placeholder="https://...">
          <?php if (field_error('capa_url')): ?><div class="lp-field-error"><?= e((string) field_error('capa_url')) ?></div><?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-3 align-items-center">
          <button type="submit" class="btn-lp-moss" data-loading-text="Salvando...">Salvar edição</button>
          <a class="lp-inline-link" href="<?= e(url('livro.php?id=' . (int) $registro['id_livro'])) ?>">Cancelar</a>
        </div>
      </form>

    <?php elseif ($tipo === 'anotacao'): ?>
      <?php
        $meuProgressoPct = (float) ($extras['meuProgressoPct'] ?? 0.0);
        $etapas = is_array($extras['etapas'] ?? null) ? $extras['etapas'] : [];
      ?>
      <form method="POST" action="<?= e(url('actions/anotacoes/salvar.php')) ?>" style="max-width: 720px;" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="id_anotacao" value="<?= (int) $registro['id_anotacao'] ?>">
        <input type="hidden" name="id_ciclo" value="<?= (int) $registro['id_ciclo'] ?>">

        <p class="lp-meta-line mb-4">
          <?= e((string) $registro['clube_nome']) ?>
          <?php if (!empty($registro['livro_titulo'])): ?>
            &mdash; <strong><?= e((string) $registro['livro_titulo']) ?></strong>
          <?php endif; ?>
          &mdash; seu progresso atual: <strong><?= e(formatarPercentual($meuProgressoPct)) ?></strong>
        </p>

        <div class="row g-4 mb-4">
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="tipo_anotacao">Tipo</label>
            <select id="tipo_anotacao" name="tipo" class="form-select lp-line-input" required>
              <?php foreach ($rotulosTipo as $chave => $label): ?>
                <option value="<?= e($chave) ?>"<?= old('tipo', (string) $registro['tipo']) === $chave ? ' selected' : '' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="posicao_pct">Posição no livro (%)</label>
            <input type="number" step="0.01" min="0" max="<?= number_format($meuProgressoPct, 2, '.', '') ?>" id="posicao_pct" name="posicao_pct" class="form-control lp-line-input<?= field_error('posicao_pct') ? ' is-invalid' : '' ?>" value="<?= e(old('posicao_pct', (string) $registro['posicao_pct'])) ?>" required>
            <?php if (field_error('posicao_pct')): ?><div class="lp-field-error"><?= e((string) field_error('posicao_pct')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-4">
            <label class="lp-field-label-caps" for="id_etapa">Etapa</label>
            <select id="id_etapa" name="id_etapa" class="form-select lp-line-input">
              <option value="">Sem etapa específica</option>
              <?php foreach ($etapas as $et): ?>
                <option value="<?= (int) $et['id_etapa'] ?>"<?= old('id_etapa', (string) ($registro['id_etapa'] ?? '')) === (string) $et['id_etapa'] ? ' selected' : '' ?>>
                  Etapa <?= (int) $et['ordem'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label class="lp-field-label-caps" for="titulo">Título curto (opcional)</label>
          <input type="text" id="titulo" name="titulo" class="form-control lp-line-input" value="<?= e(old('titulo', (string) ($registro['titulo'] ?? ''))) ?>" maxlength="160">
        </div>

        <div class="mb-5">
          <label class="lp-field-label-caps" for="conteudo_anotacao">Conteúdo</label>
          <textarea id="conteudo_anotacao" name="conteudo" class="form-control lp-line-input<?= field_error('conteudo') ? ' is-invalid' : '' ?>" rows="5" maxlength="2500" required><?= e(old('conteudo', (string) $registro['conteudo'])) ?></textarea>
          <div class="text-end mt-1"><small class="lp-muted" id="contador_anotacao">0 / 2500 caracteres</small></div>
          <?php if (field_error('conteudo')): ?><div class="lp-field-error"><?= e((string) field_error('conteudo')) ?></div><?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-3 align-items-center">
          <button type="submit" class="btn-lp-moss" data-loading-text="Salvando...">Salvar anotação</button>
          <a class="lp-inline-link" href="<?= e(url('caderno.php?id=' . (int) $registro['id_ciclo'])) ?>">Cancelar</a>
        </div>
      </form>

    <?php elseif ($tipo === 'etapa'): ?>
      <form method="POST" action="<?= e(url('actions/ciclos/salvar-etapa.php')) ?>" style="max-width: 720px;" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="id_etapa" value="<?= (int) $registro['id_etapa'] ?>">
        <input type="hidden" name="id_ciclo" value="<?= (int) $registro['id_ciclo'] ?>">

        <div class="row g-4 mb-4">
          <div class="col-sm-3">
            <label class="lp-field-label-caps" for="ordem">Ordem</label>
            <input type="number" min="1" max="50" id="ordem" name="ordem" class="form-control lp-line-input<?= field_error('ordem') ? ' is-invalid' : '' ?>" value="<?= e(old('ordem', (string) $registro['ordem'])) ?>" required>
            <?php if (field_error('ordem')): ?><div class="lp-field-error"><?= e((string) field_error('ordem')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-9">
            <label class="lp-field-label-caps" for="titulo">Título da etapa</label>
            <input type="text" id="titulo" name="titulo" class="form-control lp-line-input<?= field_error('titulo') ? ' is-invalid' : '' ?>" value="<?= e(old('titulo', (string) $registro['titulo'])) ?>" maxlength="140" required>
            <?php if (field_error('titulo')): ?><div class="lp-field-error"><?= e((string) field_error('titulo')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-sm-6">
            <label class="lp-field-label-caps" for="percentual_alvo">Percentual-alvo (%)</label>
            <input type="number" step="1" min="0" max="100" id="percentual_alvo" name="percentual_alvo" class="form-control lp-line-input<?= field_error('percentual_alvo') ? ' is-invalid' : '' ?>" value="<?= e(old('percentual_alvo', (string) $registro['percentual_alvo'])) ?>" required>
            <?php if (field_error('percentual_alvo')): ?><div class="lp-field-error"><?= e((string) field_error('percentual_alvo')) ?></div><?php endif; ?>
          </div>
          <div class="col-sm-6">
            <label class="lp-field-label-caps" for="data_limite">Data sugerida</label>
            <input type="date" id="data_limite" name="data_limite" class="form-control lp-line-input<?= field_error('data_limite') ? ' is-invalid' : '' ?>" value="<?= e(old('data_limite', (string) ($registro['data_limite'] ?? ''))) ?>">
            <?php if (field_error('data_limite')): ?><div class="lp-field-error"><?= e((string) field_error('data_limite')) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="mb-5">
          <label class="lp-field-label-caps" for="pergunta">Pergunta de discussão</label>
          <textarea id="pergunta" name="pergunta" class="form-control lp-line-input<?= field_error('pergunta') ? ' is-invalid' : '' ?>" rows="3" maxlength="700"><?= e(old('pergunta', (string) ($registro['pergunta'] ?? ''))) ?></textarea>
          <?php if (field_error('pergunta')): ?><div class="lp-field-error"><?= e((string) field_error('pergunta')) ?></div><?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-3 align-items-center">
          <button type="submit" class="btn-lp-moss" data-loading-text="Salvando...">Salvar etapa</button>
          <a class="lp-inline-link" href="<?= e(url('ciclo.php?id=' . (int) $registro['id_ciclo'] . '#etapas')) ?>">Cancelar</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
