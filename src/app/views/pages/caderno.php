<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var array $usuario
 * @var array $clube
 * @var int $totalMembros
 * @var array $ciclo
 * @var float $meuProgressoPct
 * @var array $etapas
 * @var array $anotacoes
 * @var string $tipoFiltro
 * @var int $etapaFiltro
 * @var bool $abrirNova
 */

$idCiclo = (int) $ciclo['id_ciclo'];
$clubeAtivo = (($clube['status'] ?? 'ativo') === 'ativo');
$emAndamento = ($ciclo['status'] === 'em_andamento') && $clubeAtivo;
$posicaoPadrao = rtrim(rtrim(number_format($meuProgressoPct, 2, '.', ''), '0'), '.');

$rotulosTipo = [
    'grifo' => 'Grifo',
    'duvida' => 'Dúvida',
    'teoria' => 'Teoria',
    'reflexao' => 'Reflexão',
    'comentario' => 'Comentário',
];

$totalProtegidas = 0;
foreach ($anotacoes as $an) {
    if ((int) $an['eh_spoiler'] === 1) {
        $totalProtegidas++;
    }
}
?>
<?php View::component('club-nav', [
    'clube' => $clube,
    'idCicloAtivo' => $idCiclo,
    'totalMembros' => $totalMembros,
    'abaAtiva' => 'caderno',
    'mostrarAbas' => true,
]); ?>

<!-- nova anotação e filtros -->
<section class="lp-band-ivory py-5">
  <div class="lp-container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <span class="lp-section-label lp-section-label-moss">
          <?= e((string) ($ciclo['livro_titulo'] ?: 'Ciclo de leitura')) ?>
        </span>
        <h2 class="mt-1 mb-2">Caderno de Margens</h2>
        <p class="lp-muted small mb-0" style="max-width: 60ch;">
          Sua leitura atual está em <strong><?= e(formatarPercentual($meuProgressoPct)) ?></strong>.
          Comentários acima desse ponto permanecem retidos no servidor até você avançar ou optar por revelar.
          <?php if ($emAndamento): ?>
            <a href="<?= e(url('ciclo.php?id=' . $idCiclo)) ?>">Atualizar progresso</a>.
          <?php endif; ?>
        </p>
      </div>

      <?php if ($emAndamento): ?>
        <button
          type="button"
          class="btn-lp-moss"
          data-bs-toggle="collapse"
          data-bs-target="#painelNovaAnotacao"
          aria-expanded="<?= $abrirNova ? 'true' : 'false' ?>"
          aria-controls="painelNovaAnotacao"
        >
          Nova anotação
        </button>
      <?php endif; ?>
    </div>

    <?php if ($emAndamento): ?>
      <div class="collapse<?= $abrirNova ? ' show' : '' ?>" id="painelNovaAnotacao">
        <div class="pt-4 pb-5 mb-4" style="border-top: 1px solid var(--lp-border-strong); border-bottom: 1px solid var(--lp-border-strong);">
          <div class="row g-5">
            <div class="col-lg-4">
              <span class="lp-section-label lp-section-label-moss mb-2">Proteção contra spoilers</span>
              <h3 class="h4 mb-2">Escrever à margem</h3>
              <p class="lp-muted small mb-0">
                Informe em qual porcentagem da obra esse trecho aparece (até <strong><?= e(formatarPercentual($meuProgressoPct)) ?></strong>). Quem estiver atrás desse ponto não verá o texto por acidente.
              </p>
            </div>

            <div class="col-lg-7 offset-lg-1 lp-col-divider">
              <form method="POST" action="<?= e(url('actions/anotacoes/salvar.php')) ?>" novalidate>
                <?= Csrf::field() ?>
                <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">

                <div class="row g-4 mb-4">
                  <div class="col-sm-4">
                    <label class="lp-field-label-caps" for="tipo_anotacao">Tipo</label>
                    <select id="tipo_anotacao" name="tipo" class="form-select lp-line-input" required>
                      <?php foreach ($rotulosTipo as $chave => $label): ?>
                        <option value="<?= e($chave) ?>"<?= old('tipo', 'reflexao') === $chave ? ' selected' : '' ?>>
                          <?= e($label) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-sm-4">
                    <label class="lp-field-label-caps" for="posicao_pct">Posição no livro (%)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      max="<?= number_format($meuProgressoPct, 2, '.', '') ?>"
                      id="posicao_pct"
                      name="posicao_pct"
                      class="form-control lp-line-input<?= field_error('posicao_pct') ? ' is-invalid' : '' ?>"
                      value="<?= e(old('posicao_pct', $posicaoPadrao)) ?>"
                      required
                    >
                    <?php if (field_error('posicao_pct')): ?>
                      <div class="lp-field-error"><?= e((string) field_error('posicao_pct')) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="col-sm-4">
                    <label class="lp-field-label-caps" for="etapa_anotacao">Etapa (opcional)</label>
                    <select id="etapa_anotacao" name="id_etapa" class="form-select lp-line-input">
                      <option value="">Sem etapa específica</option>
                      <?php foreach ($etapas as $et): ?>
                        <option value="<?= (int) $et['id_etapa'] ?>"<?= old('id_etapa') === (string) $et['id_etapa'] ? ' selected' : '' ?>>
                          Etapa <?= (int) $et['ordem'] ?> (<?= e(formatarPercentual((float) $et['percentual_alvo'])) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="lp-field-label-caps" for="titulo_anotacao">Título curto (opcional)</label>
                  <input
                    type="text"
                    id="titulo_anotacao"
                    name="titulo"
                    class="form-control lp-line-input"
                    value="<?= e(old('titulo')) ?>"
                    maxlength="160"
                    placeholder="Ex.: A primeira impressão sobre o narrador"
                  >
                </div>

                <div class="mb-4">
                  <label class="lp-field-label-caps" for="conteudo_anotacao">Sua anotação</label>
                  <textarea
                    id="conteudo_anotacao"
                    name="conteudo"
                    class="form-control lp-line-input<?= field_error('conteudo') ? ' is-invalid' : '' ?>"
                    rows="4"
                    maxlength="2500"
                    placeholder="Compartilhe um grifo, uma pergunta ou uma reflexão sobre este trecho..."
                    required
                  ><?= e(old('conteudo')) ?></textarea>
                  <div class="d-flex justify-content-between mt-1">
                    <small class="lp-muted">Seu comentário ficará protegido para quem ainda não chegou a essa parte.</small>
                    <small class="lp-muted" id="contador_anotacao">0 / 2500 caracteres</small>
                  </div>
                  <?php if (field_error('conteudo')): ?>
                    <div class="lp-field-error"><?= e((string) field_error('conteudo')) ?></div>
                  <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center">
                  <button type="submit" class="btn-lp-moss btn-lp-sm" data-loading-text="Publicando...">
                    Publicar no caderno
                  </button>
                  <button type="button" class="btn btn-link btn-sm p-0 lp-muted" data-bs-toggle="collapse" data-bs-target="#painelNovaAnotacao">
                    Fechar
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- filtros -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3" style="border-bottom: 1px solid var(--lp-border);">
      <nav class="lp-filter-nav pt-0" aria-label="Filtrar anotações por tipo">
        <?php $baseEtapa = $etapaFiltro > 0 ? '&etapa=' . $etapaFiltro : ''; ?>
        <a class="lp-filter-link<?= $tipoFiltro === '' ? ' is-active' : '' ?>" href="<?= e(url('caderno.php?id=' . $idCiclo . $baseEtapa)) ?>">
          Todas
        </a>
        <?php foreach ($rotulosTipo as $chave => $label): ?>
          <a class="lp-filter-link<?= $tipoFiltro === $chave ? ' is-active' : '' ?>" href="<?= e(url('caderno.php?id=' . $idCiclo . '&tipo=' . $chave . $baseEtapa)) ?>">
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <?php if ($etapas !== []): ?>
        <form method="GET" action="<?= e(url('caderno.php')) ?>" class="d-flex align-items-center gap-2">
          <input type="hidden" name="id" value="<?= $idCiclo ?>">
          <?php if ($tipoFiltro !== ''): ?>
            <input type="hidden" name="tipo" value="<?= e($tipoFiltro) ?>">
          <?php endif; ?>
          <label class="visually-hidden" for="filtro_etapa">Etapa</label>
          <select id="filtro_etapa" name="etapa" class="form-select lp-line-input py-1" style="min-height: 36px; font-size: 0.88rem;" onchange="this.form.submit()">
            <option value="">Todas as etapas</option>
            <?php foreach ($etapas as $et): ?>
              <option value="<?= (int) $et['id_etapa'] ?>"<?= $etapaFiltro === (int) $et['id_etapa'] ? ' selected' : '' ?>>
                Etapa <?= (int) $et['ordem'] ?>: <?= e((string) $et['titulo']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- lista de anotações -->
<section class="lp-band-paper">
  <div class="lp-container">
    <p class="lp-meta-line mb-4">
      <?php $totalNotas = count($anotacoes); ?>
      <?= $totalNotas ?> <?= $totalNotas === 1 ? 'anotação' : 'anotações' ?>
      <?php if ($totalProtegidas > 0): ?>
        &mdash; <strong><?= $totalProtegidas ?> <?= $totalProtegidas === 1 ? 'protegida' : 'protegidas' ?></strong> à frente da sua leitura atual
      <?php endif; ?>
    </p>

    <?php if ($anotacoes === []): ?>
      <?php View::component('empty-state', [
          'titulo' => 'Nenhuma anotação por aqui ainda',
          'mensagem' => ($tipoFiltro !== '' || $etapaFiltro > 0)
              ? 'Nenhuma nota corresponde a este filtro. Limpe os filtros ou registre sua primeira impressão.'
              : 'O Caderno de Margens está em branco. Compartilhe um grifo, dúvida ou reflexão sobre o que você leu até agora.',
          'ctaTexto' => ($tipoFiltro !== '' || $etapaFiltro > 0) ? 'Ver todas as anotações' : ($emAndamento ? 'Escrever primeira anotação' : ''),
          'ctaUrl' => ($tipoFiltro !== '' || $etapaFiltro > 0) ? 'caderno.php?id=' . $idCiclo : 'caderno.php?id=' . $idCiclo . '&nova=1',
      ]); ?>
    <?php else: ?>
      <div class="d-grid gap-4" style="max-width: 820px;">
        <?php foreach ($anotacoes as $an): ?>
          <?php
            $ehSpoiler = ((int) $an['eh_spoiler'] === 1);
            $tipo = (string) $an['tipo'];
            $tipoRotulo = $rotulosTipo[$tipo] ?? $tipo;
            $posFmt = formatarPercentual((float) $an['posicao_pct']);
            $ehAutor = ((int) $an['id_usuario'] === $usuario['id_usuario']);
          ?>

          <?php if ($ehSpoiler): ?>
            <!-- spoiler protegido: texto não vem no HTML -->
            <article class="lp-spoiler-entry" data-spoiler-card="true">
              <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2 small" style="color: rgba(243, 235, 221, 0.8);">
                <span>
                  <strong style="color: var(--lp-brass);"><?= e($tipoRotulo) ?></strong>
                  &middot; <?= e($posFmt) ?> da obra
                  <?php if (!empty($an['etapa_titulo'])): ?>
                    &middot; <?= e((string) $an['etapa_titulo']) ?>
                  <?php endif; ?>
                </span>
                <span>Por @<?= e((string) $an['autor_apelido']) ?></span>
              </div>

              <h3 class="h4 mb-2" style="color: var(--lp-paper-light);">
                Protegido até você alcançar <?= e($posFmt) ?> da leitura
              </h3>
              <p class="small mb-3" style="color: rgba(251, 248, 241, 0.82); max-width: 56ch;">
                O texto desta anotação não foi carregado nesta página para preservar a sua experiência. Avance na leitura ou escolha revelar conscientemente abaixo.
              </p>

              <div class="d-flex flex-wrap align-items-center gap-3">
                <button
                  type="button"
                  class="btn-lp-ghost-light btn-lp-sm"
                  data-revelar-id="<?= (int) $an['id_anotacao'] ?>"
                  data-posicao-fmt="<?= e($posFmt) ?>"
                  data-api-url="<?= e(url('api/revelar-anotacao.php')) ?>"
                >
                  Revelar mesmo assim
                </button>
                <span class="small" style="color: #F6C5BD;" data-spoiler-feedback="true" aria-live="polite"></span>
              </div>
            </article>
          <?php else: ?>
            <article class="lp-margin-note tipo-<?= e($tipo) ?>">
              <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2 lp-meta-line">
                <span>
                  <strong style="color: var(--lp-ink);"><?= e($tipoRotulo) ?></strong>
                  &middot; <?= e($posFmt) ?>
                  <?php if (!empty($an['etapa_titulo'])): ?>
                    &middot; Etapa <?= (int) $an['etapa_ordem'] ?> (<?= e((string) $an['etapa_titulo']) ?>)
                  <?php endif; ?>
                </span>

                <?php if ($ehAutor && $emAndamento): ?>
                  <span class="d-inline-flex gap-3 small">
                    <a class="lp-muted" href="<?= e(url('atualizar.php?tipo=anotacao&id=' . (int) $an['id_anotacao'])) ?>">Editar</a>
                    <a class="text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=anotacao&id=' . (int) $an['id_anotacao'])) ?>">Excluir</a>
                  </span>
                <?php endif; ?>
              </div>

              <?php if (!empty($an['titulo'])): ?>
                <h3 class="h4 mb-2"><?= e((string) $an['titulo']) ?></h3>
              <?php endif; ?>

              <p class="lp-editorial-prose mb-3" style="white-space: pre-line;"><?= e((string) $an['conteudo']) ?></p>

              <p class="lp-meta-line small mb-0">
                Por @<?= e((string) $an['autor_apelido']) ?> &mdash; <?= e(formatarDataHora((string) $an['criado_em'])) ?>
              </p>
            </article>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
