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
 * @var bool $ehResponsavel
 * @var array $indicacoes
 * @var int|null $meuVotoId
 * @var array $livrosAcervo
 * @var array $edicoesLivro
 * @var array|null $meuProgresso
 * @var array $resumoColetivo
 * @var array $etapas
 * @var string $capaLivro
 */

$idCiclo = (int) $ciclo['id_ciclo'];
$clubeAtivo = (($clube['status'] ?? 'ativo') === 'ativo');
$statusCiclo = (string) $ciclo['status'];
$emAndamento = ($statusCiclo === 'em_andamento');
$emVotacao = ($statusCiclo === 'votacao');
$encerrado = in_array($statusCiclo, ['encerrado', 'cancelado'], true);
$podeAlterar = $clubeAtivo && !$encerrado;

$meuPct = $meuProgresso !== null ? (float) $meuProgresso['progresso_pct'] : 0.0;
$formatoAtual = old('formato', (string) ($meuProgresso['formato'] ?? 'kindle'));
$edicaoAtualId = (int) old('id_edicao', (string) ($meuProgresso['id_edicao'] ?? 0));
$paginaAtualVal = old('pagina_atual', isset($meuProgresso['pagina_atual']) ? (string) $meuProgresso['pagina_atual'] : '');
$kindleVal = old('percentual_kindle', isset($meuProgresso['percentual_kindle']) ? (string) $meuProgresso['percentual_kindle'] : '');
$statusLeituraVal = old('status_leitura', (string) ($meuProgresso['status_leitura'] ?? 'lendo'));

$edicoesComPaginas = array_values(array_filter(
    $edicoesLivro,
    static fn(array $ed): bool => !empty($ed['total_paginas']) && (int) $ed['total_paginas'] > 0
));

$encontroInputVal = '';
if (!empty($ciclo['encontro_em'])) {
    $ts = strtotime((string) $ciclo['encontro_em']);
    if ($ts !== false) {
        $encontroInputVal = date('Y-m-d\TH:i', $ts);
    }
}
?>
<?php View::component('club-nav', [
    'clube' => $clube,
    'idCicloAtivo' => $idCiclo,
    'totalMembros' => $totalMembros,
    'abaAtiva' => 'ciclo',
    'mostrarAbas' => true,
]); ?>

<?php if ($emVotacao): ?>
  <!-- votação -->
  <section class="lp-band-ivory">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-8">
          <span class="lp-section-label mb-2">Votação aberta</span>
          <h2 class="mb-2"><?= e((string) ($ciclo['titulo_ciclo'] ?: 'Escolha da próxima leitura')) ?></h2>
          <p class="lp-muted mb-4">
            Cada participante tem direito a um voto e pode trocar sua escolha enquanto a votação permanecer aberta.
            <?php if (!empty($ciclo['data_limite'])): ?>
              Prazo previsto: <strong><?= e(formatarData((string) $ciclo['data_limite'])) ?></strong>.
            <?php endif; ?>
          </p>

          <?php if ($indicacoes === []): ?>
            <?php View::component('empty-state', [
                'titulo' => 'Nenhum livro indicado ainda',
                'mensagem' => 'Use o formulário ao lado para indicar o primeiro título do acervo compartilhado.',
            ]); ?>
          <?php else: ?>
            <div class="lp-entry-list">
              <?php foreach ($indicacoes as $idx => $ind): ?>
                <?php
                  $idInd = (int) $ind['id_indicacao'];
                  $votos = (int) $ind['total_votos'];
                  $ehMeuVoto = ($meuVotoId === $idInd);
                ?>
                <article class="lp-entry-row">
                  <div class="row g-4 align-items-center">
                    <div class="col-3 col-sm-2">
                      <?php View::component('book-cover', [
                          'titulo' => (string) $ind['livro_titulo'],
                          'autor' => (string) $ind['livro_autor'],
                          'ano' => $ind['livro_ano'] ?? '',
                          'capaUrl' => (string) ($ind['livro_capa'] ?? ''),
                          'tomIndex' => $idx,
                      ]); ?>
                    </div>

                    <div class="col-9 col-sm-7">
                      <h3 class="mb-1">
                        <a class="lp-entry-title-link" href="<?= e(url('livro.php?id=' . (int) $ind['id_livro'])) ?>">
                          <?= e((string) $ind['livro_titulo']) ?>
                        </a>
                      </h3>
                      <p class="lp-meta-line mb-2">
                        <?= e((string) $ind['livro_autor']) ?> &mdash; indicado por @<?= e((string) $ind['indicado_por_apelido']) ?>
                      </p>
                      <?php if (!empty($ind['justificativa'])): ?>
                        <p class="lp-muted small mb-0">“<?= e((string) $ind['justificativa']) ?>”</p>
                      <?php endif; ?>
                      <?php if ($podeAlterar && ((int) $ind['id_usuario'] === $usuario['id_usuario'] || $ehResponsavel)): ?>
                        <p class="mt-2 mb-0 small">
                          <a class="text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=indicacao&id=' . $idInd)) ?>">Remover indicação</a>
                        </p>
                      <?php endif; ?>
                    </div>

                    <div class="col-sm-3 text-sm-end">
                      <p class="mb-2">
                        <strong style="font-size: 1.15rem; color: var(--lp-moss-deep);"><?= $votos ?></strong>
                        <span class="lp-meta-line"> <?= $votos === 1 ? 'voto' : 'votos' ?></span>
                      </p>

                      <?php if ($ehMeuVoto): ?>
                        <span class="lp-badge lp-badge-moss">Seu voto atual</span>
                      <?php elseif ($podeAlterar): ?>
                        <form method="POST" action="<?= e(url('actions/ciclos/votar.php')) ?>">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">
                          <input type="hidden" name="id_indicacao" value="<?= $idInd ?>">
                          <button type="submit" class="btn-lp-secondary btn-lp-sm">
                            <?= $meuVotoId !== null ? 'Trocar voto' : 'Votar neste' ?>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <aside class="col-lg-4 lp-col-divider">
          <?php if (!$podeAlterar): ?>
            <p class="lp-muted small mb-0">
              Este clube está arquivado; novas indicações e votos estão desabilitados.
            </p>
          <?php else: ?>
            <div class="mb-5">
              <h3 class="h4 mb-2">Indicar um livro</h3>
              <p class="lp-muted small mb-3">
                Escolha uma obra do acervo compartilhado para concorrer neste ciclo.
              </p>
              <form method="POST" action="<?= e(url('actions/ciclos/indicar.php')) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">

                <div class="mb-3">
                  <label class="lp-field-label-caps" for="ind_livro">Obra do acervo</label>
                  <select id="ind_livro" name="id_livro" class="form-select lp-line-input" required>
                    <option value="">Selecione um livro</option>
                    <?php foreach ($livrosAcervo as $lv): ?>
                      <option value="<?= (int) $lv['id_livro'] ?>">
                        <?= e((string) $lv['titulo']) ?> — <?= e((string) $lv['autor_principal']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="lp-help-text">
                    Não achou o título? <a href="<?= e(url('acervo.php?novo=1')) ?>">Cadastre no Acervo</a>.
                  </div>
                </div>

                <div class="mb-4">
                  <label class="lp-field-label-caps" for="ind_just">Por que ler este livro? (opcional)</label>
                  <textarea id="ind_just" name="justificativa" class="form-control lp-line-input" rows="2" maxlength="500" placeholder="Conte em poucas linhas por que recomenda esta leitura."></textarea>
                </div>

                <button type="submit" class="btn-lp-moss btn-lp-sm">Indicar livro</button>
              </form>
            </div>

            <?php if ($ehResponsavel && $indicacoes !== []): ?>
              <div class="pt-4" style="border-top: 1px solid var(--lp-border);">
                <span class="lp-section-label mb-2">Responsável</span>
                <h3 class="h4 mb-2">Encerrar votação</h3>
                <p class="lp-muted small mb-3">
                  Ao encerrar, o livro mais votado inicia a leitura. Em caso de empate, você pode escolher o vencedor abaixo.
                </p>

                <form
                  method="POST"
                  action="<?= e(url('actions/ciclos/encerrar-votacao.php')) ?>"
                  data-confirm="Deseja encerrar a votação e iniciar oficialmente a leitura do livro selecionado?"
                >
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">

                  <div class="mb-3">
                    <label class="lp-field-label-caps" for="vencedor_sel">Livro vencedor</label>
                    <select id="vencedor_sel" name="id_indicacao_vencedora" class="form-select lp-line-input">
                      <?php foreach ($indicacoes as $ind): ?>
                        <option value="<?= (int) $ind['id_indicacao'] ?>">
                          <?= e((string) $ind['livro_titulo']) ?> (<?= (int) $ind['total_votos'] ?> <?= (int) $ind['total_votos'] === 1 ? 'voto' : 'votos' ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <button type="submit" class="btn-lp-primary btn-lp-sm">Encerrar e iniciar leitura</button>
                </form>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </section>

<?php else: ?>
  <!-- leitura em andamento / encerrada -->
  <section class="lp-band-ivory">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-7">
          <div class="row g-4 align-items-center mb-5 pb-4" style="border-bottom: 1px solid var(--lp-border);">
            <?php if (!empty($ciclo['livro_titulo'])): ?>
              <div class="col-4 col-sm-3">
                <?php View::component('book-cover', [
                    'titulo' => (string) $ciclo['livro_titulo'],
                    'autor' => (string) ($ciclo['livro_autor'] ?? ''),
                    'ano' => $ciclo['livro_ano'] ?? '',
                    'capaUrl' => $capaLivro,
                ]); ?>
              </div>
            <?php endif; ?>

            <div class="<?= !empty($ciclo['livro_titulo']) ? 'col-8 col-sm-9' : 'col-12' ?>">
              <span class="lp-section-label lp-section-label-moss mb-1">
                <?= $emAndamento ? 'Leitura em andamento' : 'Ciclo encerrado' ?>
                <?php if (!empty($ciclo['titulo_ciclo'])): ?>
                  &middot; <?= e((string) $ciclo['titulo_ciclo']) ?>
                <?php endif; ?>
              </span>
              <h2 class="mb-1">
                <?php if (!empty($ciclo['id_livro_escolhido'])): ?>
                  <a class="text-decoration-none" style="color: inherit;" href="<?= e(url('livro.php?id=' . (int) $ciclo['id_livro_escolhido'])) ?>">
                    <?= e((string) $ciclo['livro_titulo']) ?>
                  </a>
                <?php else: ?>
                  Leitura do ciclo
                <?php endif; ?>
              </h2>
              <?php if (!empty($ciclo['livro_autor'])): ?>
                <p class="lp-muted mb-3"><?= e((string) $ciclo['livro_autor']) ?></p>
              <?php endif; ?>

              <div class="d-flex flex-wrap gap-3 align-items-center">
                <a class="btn-lp-moss btn-lp-sm" href="<?= e(url('caderno.php?id=' . $idCiclo)) ?>">
                  Abrir Caderno de Margens
                </a>
                <?php if (!empty($ciclo['data_limite']) && $emAndamento): ?>
                  <span class="lp-meta-line">Prazo sugerido: <strong><?= e(formatarData((string) $ciclo['data_limite'])) ?></strong></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- progresso pessoal -->
          <div aria-labelledby="sec-meu-progresso">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
              <h3 id="sec-meu-progresso" class="mb-0">Meu progresso</h3>
              <strong style="color: var(--lp-moss-deep); font-size: 1.15rem;">
                <?= e(formatarPercentual($meuPct)) ?>
              </strong>
            </div>

            <div class="lp-progress-track mb-2" role="progressbar" aria-valuenow="<?= (int) round($meuPct) ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Seu progresso na leitura">
              <div class="lp-progress-fill" id="progresso-preview-barra" style="width: <?= number_format($meuPct, 2, '.', '') ?>%;"></div>
            </div>

            <p class="lp-meta-line small mb-4" id="progresso-preview-texto">
              <?php if ($meuProgresso === null): ?>
                Você ainda não informou onde está no livro. Anotações acima de 0% ficam protegidas contra spoilers.
              <?php elseif ($meuProgresso['formato'] === 'kindle'): ?>
                Kindle &middot; <?= e(formatarPercentual($meuPct)) ?> (atualizado em <?= e(formatarDataHora((string) $meuProgresso['atualizado_em'])) ?>)
              <?php else: ?>
                Edição física (<?= e((string) ($meuProgresso['editora'] ?: 'Físico')) ?>) &middot; página <?= (int) $meuProgresso['pagina_atual'] ?> de <?= (int) $meuProgresso['total_paginas'] ?> (~<?= e(formatarPercentual($meuPct)) ?>)
              <?php endif; ?>
            </p>

            <?php if ($emAndamento && $podeAlterar): ?>
              <form id="form-progresso" method="POST" action="<?= e(url('actions/ciclos/atualizar-progresso.php')) ?>" class="pt-3" style="border-top: 1px solid var(--lp-border);">
                <?= Csrf::field() ?>
                <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">

                <div class="mb-4">
                  <span class="lp-field-label-caps mb-2">Como você está lendo?</span>
                  <div class="d-flex flex-wrap gap-4">
                    <label class="d-inline-flex align-items-center gap-2" style="cursor: pointer;">
                      <input type="radio" name="formato" value="kindle" class="form-check-input mt-0"<?= $formatoAtual === 'kindle' ? ' checked' : '' ?>>
                      <span>Kindle / e-reader (%)</span>
                    </label>
                    <label class="d-inline-flex align-items-center gap-2" style="cursor: pointer;">
                      <input type="radio" name="formato" value="fisico" class="form-check-input mt-0"<?= $formatoAtual === 'fisico' ? ' checked' : '' ?>>
                      <span>Livro físico (páginas)</span>
                    </label>
                  </div>
                </div>

                <div id="campos-kindle" class="row g-4 mb-4<?= $formatoAtual === 'kindle' ? '' : ' d-none' ?>">
                  <div class="col-sm-6">
                    <label class="lp-field-label-caps" for="percentual_kindle">Porcentagem atual (0 a 100)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      id="percentual_kindle"
                      name="percentual_kindle"
                      class="form-control lp-line-input"
                      value="<?= e($kindleVal) ?>"
                      placeholder="Ex.: 45"
                      <?= $formatoAtual === 'kindle' ? '' : 'disabled' ?>
                    >
                  </div>
                </div>

                <div id="campos-fisico" class="row g-4 mb-4<?= $formatoAtual === 'fisico' ? '' : ' d-none' ?>">
                  <?php if ($edicoesComPaginas === []): ?>
                    <div class="col-12">
                      <p class="lp-muted small mb-0">
                        Este livro ainda não tem nenhuma edição física com total de páginas cadastrado.
                        <a href="<?= e(url('livro.php?id=' . (int) $ciclo['id_livro_escolhido'])) ?>">Cadastre a sua edição na ficha do livro</a> ou acompanhe informando o percentual aproximado.
                      </p>
                    </div>
                  <?php else: ?>
                    <div class="col-md-7">
                      <label class="lp-field-label-caps" for="id_edicao">Sua edição física</label>
                      <select id="id_edicao" name="id_edicao" class="form-select lp-line-input"<?= $formatoAtual === 'fisico' ? '' : ' disabled' ?>>
                        <option value="">Selecione a edição</option>
                        <?php foreach ($edicoesComPaginas as $ed): ?>
                          <option
                            value="<?= (int) $ed['id_edicao'] ?>"
                            data-paginas="<?= (int) $ed['total_paginas'] ?>"
                            <?= $edicaoAtualId === (int) $ed['id_edicao'] ? ' selected' : '' ?>
                          >
                            <?= e((string) ($ed['editora'] ?: $ed['titulo_edicao'] ?: 'Edição')) ?> — <?= (int) $ed['total_paginas'] ?> pág.
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-5">
                      <label class="lp-field-label-caps" for="pagina_atual">Página atual</label>
                      <input
                        type="number"
                        min="0"
                        id="pagina_atual"
                        name="pagina_atual"
                        class="form-control lp-line-input"
                        value="<?= e($paginaAtualVal) ?>"
                        placeholder="Ex.: 52"
                        <?= $formatoAtual === 'fisico' ? '' : 'disabled' ?>
                      >
                    </div>
                  <?php endif; ?>
                </div>

                <div class="row g-4 align-items-end mb-3">
                  <div class="col-sm-6">
                    <label class="lp-field-label-caps" for="status_leitura">Situação</label>
                    <select id="status_leitura" name="status_leitura" class="form-select lp-line-input">
                      <option value="lendo"<?= $statusLeituraVal === 'lendo' ? ' selected' : '' ?>>Lendo</option>
                      <option value="pausado"<?= $statusLeituraVal === 'pausado' ? ' selected' : '' ?>>Pausado</option>
                      <option value="concluido"<?= $statusLeituraVal === 'concluido' ? ' selected' : '' ?>>Concluído</option>
                    </select>
                  </div>
                  <div class="col-sm-6">
                    <button type="submit" class="btn-lp-moss" data-loading-text="Atualizando...">
                      Atualizar meu progresso
                    </button>
                  </div>
                </div>

                <p class="lp-meta-line small mb-0">
                  A conversão entre páginas e porcentagem é aproximada e serve para orientar a conversa e proteger spoilers no Caderno de Margens.
                </p>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- ritmo coletivo e encontro -->
        <aside class="col-lg-4 offset-lg-1 lp-col-divider">
          <div class="mb-5">
            <span class="lp-section-label lp-section-label-moss mb-1">Ritmo do grupo</span>
            <h3 class="h4 mb-2">Acompanhamento coletivo</h3>
            <p class="lp-muted small mb-3">
              <?php if ($resumoColetivo['total_leitores'] === 0): ?>
                Nenhum participante registrou progresso ainda neste ciclo.
              <?php else: ?>
                <?= $resumoColetivo['total_leitores'] ?> <?= $resumoColetivo['total_leitores'] === 1 ? 'leitor acompanhando' : 'leitores acompanhando' ?> &middot; média aproximada de <strong><?= e(formatarPercentual($resumoColetivo['media_pct'])) ?></strong>.
              <?php endif; ?>
            </p>

            <?php if ($resumoColetivo['total_leitores'] > 0): ?>
              <div class="lp-progress-track mb-3">
                <div class="lp-progress-fill" style="width: <?= number_format($resumoColetivo['media_pct'], 2, '.', '') ?>%;"></div>
              </div>

              <ul class="list-unstyled lp-entry-list small mb-0">
                <li class="lp-entry-row-compact d-flex justify-content-between">
                  <span class="lp-muted">Início (0% – 25%)</span>
                  <span><?= $resumoColetivo['faixas']['0-25'] ?></span>
                </li>
                <li class="lp-entry-row-compact d-flex justify-content-between">
                  <span class="lp-muted">Meio da obra (26% – 50%)</span>
                  <span><?= $resumoColetivo['faixas']['26-50'] ?></span>
                </li>
                <li class="lp-entry-row-compact d-flex justify-content-between">
                  <span class="lp-muted">Segunda metade (51% – 75%)</span>
                  <span><?= $resumoColetivo['faixas']['51-75'] ?></span>
                </li>
                <li class="lp-entry-row-compact d-flex justify-content-between">
                  <span class="lp-muted">Reta final (76% – 100%)</span>
                  <span><?= $resumoColetivo['faixas']['76-100'] ?></span>
                </li>
              </ul>
            <?php endif; ?>
          </div>

          <div class="mb-5" id="encontro">
            <span class="lp-section-label mb-1">Conversa do ciclo</span>
            <h3 class="h4 mb-2">Encontro do clube</h3>

            <?php if (!empty($ciclo['encontro_em']) || !empty($ciclo['encontro_local']) || !empty($ciclo['encontro_url'])): ?>
              <div class="lp-meta-line mb-3">
                <?php if (!empty($ciclo['encontro_em'])): ?>
                  <p class="mb-1"><strong>Data:</strong> <?= e(formatarDataHora((string) $ciclo['encontro_em'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($ciclo['encontro_local'])): ?>
                  <p class="mb-1"><strong>Local:</strong> <?= e((string) $ciclo['encontro_local']) ?></p>
                <?php endif; ?>
                <?php if (!empty($ciclo['encontro_url'])): ?>
                  <p class="mb-0">
                    <a href="<?= e((string) $ciclo['encontro_url']) ?>" target="_blank" rel="noopener noreferrer">
                      Acessar link do encontro
                    </a>
                  </p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <p class="lp-muted small mb-3">Data e local do encontro ainda não foram definidos.</p>
            <?php endif; ?>

            <?php if ($ehResponsavel && $emAndamento && $podeAlterar): ?>
              <details class="mt-2">
                <summary class="small fw-medium" style="cursor: pointer; color: var(--lp-moss-deep);">
                  Definir ou alterar encontro
                </summary>
                <form method="POST" action="<?= e(url('actions/ciclos/salvar-encontro.php')) ?>" class="mt-3">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">

                  <div class="mb-3">
                    <label class="lp-field-label-caps" for="encontro_em">Data e hora</label>
                    <input type="datetime-local" id="encontro_em" name="encontro_em" class="form-control lp-line-input" value="<?= e($encontroInputVal) ?>">
                  </div>
                  <div class="mb-3">
                    <label class="lp-field-label-caps" for="encontro_local">Local ou plataforma</label>
                    <input type="text" id="encontro_local" name="encontro_local" class="form-control lp-line-input" maxlength="255" value="<?= e((string) ($ciclo['encontro_local'] ?? '')) ?>" placeholder="Ex.: Google Meet ou Café da Rua">
                  </div>
                  <div class="mb-3">
                    <label class="lp-field-label-caps" for="encontro_url">Link HTTPS (opcional)</label>
                    <input type="url" id="encontro_url" name="encontro_url" class="form-control lp-line-input" maxlength="500" value="<?= e((string) ($ciclo['encontro_url'] ?? '')) ?>" placeholder="https://...">
                  </div>
                  <button type="submit" class="btn-lp-secondary btn-lp-sm">Salvar encontro</button>
                </form>
              </details>
            <?php endif; ?>
          </div>

          <?php if ($ehResponsavel && $emAndamento && $podeAlterar): ?>
            <div class="pt-4" style="border-top: 1px solid var(--lp-border);">
              <form
                method="POST"
                action="<?= e(url('actions/ciclos/encerrar.php')) ?>"
                data-confirm="Deseja encerrar este ciclo de leitura? Ele passará para o histórico do clube."
              >
                <?= Csrf::field() ?>
                <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">
                <button type="submit" class="btn-lp-secondary btn-lp-sm">Encerrar este ciclo</button>
              </form>
            </div>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </section>

  <!-- etapas de leitura -->
  <section class="lp-band-paper" id="etapas" aria-labelledby="sec-etapas">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="<?= ($ehResponsavel && $emAndamento && $podeAlterar) ? 'col-lg-7' : 'col-lg-10' ?>">
          <span class="lp-section-label lp-section-label-moss mb-1">Marcos combinados</span>
          <h2 id="sec-etapas" class="mb-2">Etapas de leitura</h2>
          <p class="lp-muted small mb-4">
            Pontos sugeridos pelo clube para organizar a conversa ao longo do livro.
          </p>

          <?php if ($etapas === []): ?>
            <p class="lp-muted small mb-0">
              Nenhuma etapa cadastrada ainda. O grupo pode ler livremente e conversar pelo Caderno de Margens.
            </p>
          <?php else: ?>
            <div class="lp-entry-list">
              <?php foreach ($etapas as $et): ?>
                <?php
                  $alvo = (float) $et['percentual_alvo'];
                  $alcancada = ($meuPct >= $alvo);
                ?>
                <div class="lp-entry-row">
                  <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-1">
                    <span class="lp-meta-line">
                      <strong>Etapa <?= (int) $et['ordem'] ?></strong> &middot; até <?= e(formatarPercentual($alvo)) ?>
                      <?php if (!empty($et['data_limite'])): ?>
                        &middot; sugerido até <?= e(formatarData((string) $et['data_limite'])) ?>
                      <?php endif; ?>
                    </span>
                    <?php if ($alcancada): ?>
                      <small style="color: var(--lp-moss-deep); font-weight: 600;">Alcançada por você</small>
                    <?php endif; ?>
                  </div>

                  <h3 class="mb-2"><?= e((string) $et['titulo']) ?></h3>

                  <?php if (!empty($et['pergunta'])): ?>
                    <p class="lp-editorial-prose lp-muted mb-3" style="font-size: 1.08rem;">
                      “<?= e((string) $et['pergunta']) ?>”
                    </p>
                  <?php endif; ?>

                  <div class="d-flex flex-wrap gap-3 align-items-center small">
                    <a class="lp-inline-link" href="<?= e(url('caderno.php?id=' . $idCiclo . '&etapa=' . (int) $et['id_etapa'])) ?>">
                      Ver notas desta etapa
                    </a>
                    <?php if ($ehResponsavel && $emAndamento && $podeAlterar): ?>
                      <a class="lp-muted" href="<?= e(url('atualizar.php?tipo=etapa&id=' . (int) $et['id_etapa'])) ?>">Editar</a>
                      <a class="text-danger text-decoration-none" href="<?= e(url('excluir.php?tipo=etapa&id=' . (int) $et['id_etapa'])) ?>">Excluir</a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($ehResponsavel && $emAndamento && $podeAlterar): ?>
          <aside class="col-lg-4 offset-lg-1 lp-col-divider">
            <h3 class="h4 mb-2">Adicionar etapa</h3>
            <p class="lp-muted small mb-4">
              Crie um ponto de parada com uma pergunta para provocar a conversa do grupo.
            </p>
            <form method="POST" action="<?= e(url('actions/ciclos/salvar-etapa.php')) ?>">
              <?= Csrf::field() ?>
              <input type="hidden" name="id_ciclo" value="<?= $idCiclo ?>">

              <div class="mb-3">
                <label class="lp-field-label-caps" for="et_titulo">Título da etapa</label>
                <input type="text" id="et_titulo" name="titulo" class="form-control lp-line-input" maxlength="140" placeholder="Ex.: Primeiros capítulos" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="lp-field-label-caps" for="et_pct">Até (%)</label>
                  <input type="number" step="1" min="0" max="100" id="et_pct" name="percentual_alvo" class="form-control lp-line-input" placeholder="25" required>
                </div>
                <div class="col-6">
                  <label class="lp-field-label-caps" for="et_data">Data sugerida</label>
                  <input type="date" id="et_data" name="data_limite" class="form-control lp-line-input">
                </div>
              </div>

              <div class="mb-4">
                <label class="lp-field-label-caps" for="et_pergunta">Pergunta de discussão (opcional)</label>
                <textarea id="et_pergunta" name="pergunta" class="form-control lp-line-input" rows="2" maxlength="700" placeholder="O que mais chamou atenção até este trecho?"></textarea>
              </div>

              <button type="submit" class="btn-lp-moss btn-lp-sm">Salvar etapa</button>
            </form>
          </aside>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
