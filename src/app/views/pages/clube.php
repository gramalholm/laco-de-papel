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
 * @var array $clube
 * @var string $nivel
 * @var bool $ehMembroAtivo
 * @var bool $ehResponsavel
 * @var int $totalMembros
 * @var array|null $cicloAtual
 * @var array $membrosAtivos
 * @var array $convitesPendentes
 * @var array $historicoCiclos
 * @var array $livrosDisponiveis
 * @var bool $podeSair
 */

$clubeAtivo = ($clube['status'] ?? 'ativo') === 'ativo';
$idClube = (int) $clube['id_clube'];

$rotuloStatusCiclo = static function (string $status): string {
    return match ($status) {
        'votacao' => 'Votação aberta',
        'em_andamento' => 'Leitura em andamento',
        'encerrado' => 'Concluído',
        'cancelado' => 'Cancelado',
        default => $status,
    };
};
?>
<?php View::component('club-nav', [
    'clube' => $clube,
    'cicloAtual' => $cicloAtual,
    'totalMembros' => $totalMembros,
    'abaAtiva' => 'visao',
    'mostrarAbas' => $ehMembroAtivo,
]); ?>

<?php if ($nivel === 'convidado'): ?>
  <!-- convite pendente -->
  <section class="lp-band-ivory">
    <div class="lp-container">
      <div style="max-width: 600px;">
        <span class="lp-section-label mb-2">Convite pendente</span>
        <h2 class="mb-3">Você foi convidado para este clube.</h2>
        <p class="lp-muted mb-4">
          Aceite o convite para acompanhar a leitura atual, conhecer os participantes e acessar o Caderno de Margens deste grupo.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <form method="POST" action="<?= e(url('actions/clubes/responder-convite.php')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id_clube" value="<?= $idClube ?>">
            <input type="hidden" name="decisao" value="aceitar">
            <button type="submit" class="btn-lp-moss">Aceitar convite</button>
          </form>
          <form method="POST" action="<?= e(url('actions/clubes/responder-convite.php')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id_clube" value="<?= $idClube ?>">
            <input type="hidden" name="decisao" value="recusar">
            <button type="submit" class="btn-lp-secondary">Recusar</button>
          </form>
        </div>
      </div>
    </div>
  </section>

<?php elseif ($nivel === 'visitante_publico'): ?>
  <!-- visão pública (sem membros nem caderno) -->
  <section class="lp-band-ivory">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-7">
          <?php if ($cicloAtual !== null): ?>
            <div class="mb-5">
              <span class="lp-section-label lp-section-label-moss mb-2">Leitura em curso</span>
              <h2 class="mb-2">
                <?= e((string) ($cicloAtual['livro_titulo'] ?: ($cicloAtual['titulo_ciclo'] ?: $rotuloStatusCiclo((string) $cicloAtual['status'])))) ?>
              </h2>
              <?php if (!empty($cicloAtual['livro_autor'])): ?>
                <p class="lp-muted mb-3"><?= e((string) $cicloAtual['livro_autor']) ?></p>
              <?php elseif ($cicloAtual['status'] === 'votacao'): ?>
                <p class="lp-muted mb-3">O clube está em fase de indicação e votação do próximo título.</p>
              <?php endif; ?>
              <p class="lp-meta-line mb-0">
                Participe do clube para votar, registrar seu progresso e ler o Caderno de Margens sem spoilers.
              </p>
            </div>
          <?php endif; ?>

          <?php if (!empty($clube['regras'])): ?>
            <div class="pt-4" style="border-top: 1px solid var(--lp-border);">
              <h3 class="mb-2">Regras de convivência</h3>
              <p class="lp-muted mb-0" style="white-space: pre-line; max-width: 58ch;"><?= e((string) $clube['regras']) ?></p>
            </div>
          <?php endif; ?>
        </div>

        <aside class="col-lg-4 offset-lg-1 lp-col-divider">
          <span class="lp-section-label lp-section-label-moss mb-2">Participação aberta</span>
          <h2 class="h3 mb-2">Ler junto com este grupo</h2>
          <p class="lp-muted small mb-4">
            Este é um clube público. Você pode entrar diretamente e acompanhar o ciclo atual no seu ritmo.
          </p>

          <?php if (!$clubeAtivo): ?>
            <p class="lp-meta-line mb-0">Este clube está arquivado e não aceita novos participantes.</p>
          <?php elseif ($usuario !== null): ?>
            <form method="POST" action="<?= e(url('actions/clubes/entrar.php')) ?>">
              <?= Csrf::field() ?>
              <input type="hidden" name="id_clube" value="<?= $idClube ?>">
              <button type="submit" class="btn-lp-moss" data-loading-text="Entrando...">
                Participar do clube
              </button>
            </form>
          <?php else: ?>
            <div class="d-flex flex-wrap gap-2">
              <a class="btn-lp-moss" href="<?= e(url('registro.php')) ?>">Criar conta</a>
              <a class="btn-lp-secondary" href="<?= e(url('login.php')) ?>">Entrar</a>
            </div>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </section>

<?php else: ?>
  <!-- ciclo atual -->
  <section class="lp-band-ivory" aria-labelledby="sec-ciclo-atual">
    <div class="lp-container">
      <?php if ($cicloAtual !== null): ?>
        <div class="row g-5 align-items-center">
          <?php if (!empty($cicloAtual['livro_titulo'])): ?>
            <div class="col-6 col-sm-4 col-md-3">
              <?php View::component('book-cover', [
                  'titulo' => (string) $cicloAtual['livro_titulo'],
                  'autor' => (string) ($cicloAtual['livro_autor'] ?? ''),
                  'ano' => $cicloAtual['livro_ano'] ?? '',
                  'capaUrl' => (string) ($cicloAtual['livro_capa'] ?? ''),
              ]); ?>
            </div>
          <?php endif; ?>

          <div class="<?= !empty($cicloAtual['livro_titulo']) ? 'col-md-8 offset-md-1' : 'col-lg-8' ?>">
            <span class="lp-section-label lp-section-label-moss mb-2">
              <?= e($rotuloStatusCiclo((string) $cicloAtual['status'])) ?>
              <?php if (!empty($cicloAtual['titulo_ciclo'])): ?>
                &middot; <?= e((string) $cicloAtual['titulo_ciclo']) ?>
              <?php endif; ?>
            </span>

            <?php if (!empty($cicloAtual['livro_titulo'])): ?>
              <h2 id="sec-ciclo-atual" class="mb-1"><?= e((string) $cicloAtual['livro_titulo']) ?></h2>
              <?php if (!empty($cicloAtual['livro_autor'])): ?>
                <p class="fs-5 lp-muted mb-3"><?= e((string) $cicloAtual['livro_autor']) ?></p>
              <?php endif; ?>
            <?php else: ?>
              <h2 id="sec-ciclo-atual" class="mb-2">Escolha da próxima leitura</h2>
              <p class="lp-muted mb-3" style="max-width: 54ch;">
                Indique títulos do acervo compartilhado e vote no livro que vai guiar o clube neste ciclo.
              </p>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-4 lp-meta-line mb-4">
              <?php if (!empty($cicloAtual['data_limite'])): ?>
                <span>Prazo: <strong><?= e(formatarData((string) $cicloAtual['data_limite'])) ?></strong></span>
              <?php endif; ?>
              <?php if (!empty($cicloAtual['encontro_em'])): ?>
                <span>Encontro: <strong><?= e(formatarDataHora((string) $cicloAtual['encontro_em'])) ?></strong></span>
              <?php endif; ?>
              <?php if (!empty($cicloAtual['encontro_local'])): ?>
                <span>Local: <strong><?= e((string) $cicloAtual['encontro_local']) ?></strong></span>
              <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn-lp-moss" href="<?= e(url('ciclo.php?id=' . (int) $cicloAtual['id_ciclo'])) ?>">
                Entrar na sala do ciclo
              </a>
              <a class="btn-lp-secondary" href="<?= e(url('caderno.php?id=' . (int) $cicloAtual['id_ciclo'])) ?>">
                Caderno de Margens
              </a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-5 align-items-start">
          <div class="col-lg-5">
            <span class="lp-section-label mb-2">Entre ciclos</span>
            <h2 id="sec-ciclo-atual" class="mb-2">Nenhum ciclo aberto agora</h2>
            <p class="lp-muted mb-0">
              <?= $ehResponsavel && $clubeAtivo
                  ? 'Abra uma votação para o grupo escolher a próxima leitura ou defina diretamente um título do acervo.'
                  : 'Assim que o responsável iniciar um novo ciclo, a votação ou leitura aparecerá aqui.' ?>
            </p>
          </div>

          <?php if ($ehResponsavel && $clubeAtivo): ?>
            <div class="col-lg-6 offset-lg-1 lp-col-divider">
              <h3 class="mb-3">Iniciar novo ciclo</h3>
              <form method="POST" action="<?= e(url('actions/ciclos/criar.php')) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="id_clube" value="<?= $idClube ?>">

                <div class="row g-3 mb-3">
                  <div class="col-md-7">
                    <label class="lp-field-label-caps" for="titulo_ciclo">Título do ciclo (opcional)</label>
                    <input type="text" id="titulo_ciclo" name="titulo_ciclo" class="form-control lp-line-input" maxlength="120" placeholder="Ex.: Leitura de Novembro">
                  </div>
                  <div class="col-md-5">
                    <label class="lp-field-label-caps" for="data_limite">Data limite (opcional)</label>
                    <input type="date" id="data_limite" name="data_limite" class="form-control lp-line-input">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="lp-field-label-caps" for="id_livro_escolhido">Modo do ciclo</label>
                  <select id="id_livro_escolhido" name="id_livro_escolhido" class="form-select lp-line-input">
                    <option value="">Abrir em votação (membros indicam e votam)</option>
                    <?php foreach ($livrosDisponiveis as $livro): ?>
                      <option value="<?= (int) $livro['id_livro'] ?>">
                        Leitura direta: <?= e((string) $livro['titulo']) ?> — <?= e((string) $livro['autor_principal']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="lp-help-text">
                    Quer ler outra obra? <a href="<?= e(url('acervo.php?novo=1')) ?>">Cadastre no Acervo</a> antes de iniciar.
                  </div>
                </div>

                <button type="submit" class="btn-lp-moss btn-lp-sm">Iniciar ciclo</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- regras, histórico e membros -->
  <section class="lp-band-paper" id="membros">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-7">
          <?php if (!empty($clube['regras'])): ?>
            <div class="mb-5">
              <h2 class="h3 mb-3">Regras de convivência</h2>
              <p class="lp-editorial-prose lp-muted mb-0" style="white-space: pre-line; font-size: 1.12rem;">
                <?= e((string) $clube['regras']) ?>
              </p>
            </div>
          <?php endif; ?>

          <div aria-labelledby="sec-historico">
            <h2 id="sec-historico" class="h3 mb-3">Histórico de ciclos</h2>
            <?php if ($historicoCiclos === []): ?>
              <p class="lp-muted small mb-0">Os ciclos concluídos por este clube aparecerão registrados aqui.</p>
            <?php else: ?>
              <div class="lp-entry-list">
                <?php foreach ($historicoCiclos as $hc): ?>
                  <div class="lp-entry-row-compact d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                      <strong class="d-block">
                        <?= e((string) ($hc['livro_titulo'] ?: ($hc['titulo_ciclo'] ?: 'Ciclo #' . (int) $hc['id_ciclo']))) ?>
                      </strong>
                      <small class="lp-muted">
                        <?= e($rotuloStatusCiclo((string) $hc['status'])) ?>
                        <?php if (!empty($hc['data_encerramento'])): ?>
                          &mdash; encerrado em <?= e(formatarData((string) $hc['data_encerramento'])) ?>
                        <?php endif; ?>
                      </small>
                    </div>
                    <a class="lp-inline-link" href="<?= e(url('ciclo.php?id=' . (int) $hc['id_ciclo'])) ?>">
                      Ver leitura
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <aside class="col-lg-4 offset-lg-1 lp-col-divider">
          <div class="mb-5">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
              <h2 class="h3 mb-0">Participantes</h2>
              <span class="lp-meta-line"><?= count($membrosAtivos) ?></span>
            </div>

            <ul class="list-unstyled lp-entry-list mb-0">
              <?php foreach ($membrosAtivos as $m): ?>
                <li class="lp-entry-row-compact d-flex justify-content-between align-items-baseline gap-2">
                  <div>
                    <span class="d-block"><?= e((string) $m['nome']) ?></span>
                    <small class="lp-muted">@<?= e((string) $m['apelido']) ?></small>
                  </div>
                  <?php if ($m['papel'] === 'responsavel'): ?>
                    <small class="fst-italic" style="color: var(--lp-burgundy);">responsável</small>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <?php if ($ehResponsavel && $clubeAtivo): ?>
            <div class="mb-5">
              <h3 class="h4 mb-2">Convidar por apelido</h3>
              <p class="lp-muted small mb-3">
                Informe o apelido de um leitor cadastrado no Laço de Papel.
              </p>
              <form method="POST" action="<?= e(url('actions/clubes/convidar.php')) ?>" class="d-flex gap-2 align-items-end mb-3">
                <?= Csrf::field() ?>
                <input type="hidden" name="id_clube" value="<?= $idClube ?>">
                <input type="hidden" name="acao" value="convidar">
                <div class="flex-grow-1">
                  <label class="visually-hidden" for="apelido_convite">Apelido</label>
                  <input
                    type="text"
                    id="apelido_convite"
                    name="apelido"
                    class="form-control lp-line-input"
                    placeholder="Ex.: rafael"
                    data-clean-handle="true"
                    required
                  >
                </div>
                <button type="submit" class="btn-lp-moss btn-lp-sm">Convidar</button>
              </form>

              <?php if ($convitesPendentes !== []): ?>
                <p class="lp-field-label-caps mt-3 mb-2">Convites pendentes</p>
                <ul class="list-unstyled d-grid gap-2 mb-0">
                  <?php foreach ($convitesPendentes as $cp): ?>
                    <li class="d-flex justify-content-between align-items-center small">
                      <span>@<?= e((string) $cp['apelido']) ?></span>
                      <form method="POST" action="<?= e(url('actions/clubes/convidar.php')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id_clube" value="<?= $idClube ?>">
                        <input type="hidden" name="acao" value="cancelar">
                        <input type="hidden" name="id_usuario" value="<?= (int) $cp['id_usuario'] ?>">
                        <button type="submit" class="btn btn-link btn-sm p-0 text-danger text-decoration-none">Cancelar</button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="pt-4" style="border-top: 1px solid var(--lp-border);">
            <div class="d-flex flex-wrap gap-3 align-items-center">
              <?php if ($ehResponsavel && $clubeAtivo): ?>
                <a class="lp-inline-link" href="<?= e(url('clube-form.php?id=' . $idClube)) ?>">
                  Editar clube
                </a>
                <form
                  method="POST"
                  action="<?= e(url('actions/clubes/arquivar.php')) ?>"
                  data-confirm="Deseja arquivar este clube? O histórico será preservado, mas novas edições ficarão bloqueadas."
                >
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id_clube" value="<?= $idClube ?>">
                  <button type="submit" class="btn btn-link btn-sm p-0 text-danger text-decoration-none">Arquivar clube</button>
                </form>
              <?php endif; ?>

              <?php if ($podeSair): ?>
                <form
                  method="POST"
                  action="<?= e(url('actions/clubes/sair.php')) ?>"
                  data-confirm="Tem certeza que deseja sair deste clube?"
                >
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id_clube" value="<?= $idClube ?>">
                  <button type="submit" class="btn btn-link btn-sm p-0 lp-muted text-decoration-underline">Sair do clube</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
<?php endif; ?>
