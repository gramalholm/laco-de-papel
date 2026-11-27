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
 * @var array $temas
 * @var array $clubesPublicos
 * @var array $livrosAcervo
 * @var array|null $leituraEmDestaque
 * @var array $meusClubesRecentes
 * @var array $convitesPendentes
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
?>

<?php if ($usuario !== null): ?>
  <!-- leitor autenticado -->
  <section class="lp-hero-open">
    <div class="lp-container">
      <div class="row g-4 align-items-end mb-5">
        <div class="col-lg-7">
          <span class="lp-section-label lp-section-label-moss">Sua casa literária</span>
          <h1 class="mt-2 mb-2">Olá, @<?= e($usuario['apelido']) ?>.</h1>
          <p class="lp-muted mb-0" style="max-width: 52ch;">
            Acompanhe os clubes em que você participa, responda convites pendentes e volte às suas leituras sem pressa.
          </p>
        </div>
        <div class="col-lg-5 text-lg-end">
          <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
            <a class="btn-lp-moss" href="<?= e(url('clube-form.php')) ?>">Criar clube</a>
            <a class="btn-lp-secondary" href="<?= e(url('meus-clubes.php')) ?>">Ver meus clubes</a>
          </div>
        </div>
      </div>

      <?php if ($convitesPendentes !== []): ?>
        <div class="mb-5">
          <h2 class="h4 mb-3">Convites pendentes</h2>
          <div class="row g-4">
            <?php foreach ($convitesPendentes as $convite): ?>
              <div class="col-md-6">
                <article class="lp-card d-flex flex-column justify-content-between h-100">
                  <div>
                    <p class="lp-meta-line mb-1">
                      <?= e((string) $convite['tema_nome']) ?> &middot; Convite de @<?= e((string) ($convite['convidado_por_apelido'] ?? 'responsavel')) ?>
                    </p>
                    <h3 class="mb-3"><?= e((string) $convite['nome']) ?></h3>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="<?= e(url('actions/clubes/responder-convite.php')) ?>">
                      <?= Csrf::field() ?>
                      <input type="hidden" name="id_clube" value="<?= (int) $convite['id_clube'] ?>">
                      <input type="hidden" name="decisao" value="aceitar">
                      <button type="submit" class="btn-lp-moss btn-lp-sm">Aceitar</button>
                    </form>
                    <form method="POST" action="<?= e(url('actions/clubes/responder-convite.php')) ?>">
                      <?= Csrf::field() ?>
                      <input type="hidden" name="id_clube" value="<?= (int) $convite['id_clube'] ?>">
                      <input type="hidden" name="decisao" value="recusar">
                      <button type="submit" class="btn-lp-secondary btn-lp-sm">Recusar</button>
                    </form>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div aria-labelledby="sec-seus-clubes">
        <div class="lp-band-header mb-4">
          <h2 id="sec-seus-clubes" class="mb-0">Seus clubes</h2>
          <a href="<?= e(url('explorar-clubes.php')) ?>" class="btn-lp-secondary btn-lp-sm">Explorar clubes</a>
        </div>

        <?php if ($meusClubesRecentes === []): ?>
          <?php View::component('empty-state', [
              'titulo' => 'Você ainda não participa de nenhum clube',
              'mensagem' => 'Entre em uma comunidade aberta ou crie seu próprio espaço para ler com amigos.',
              'ctaTexto' => 'Explorar clubes públicos',
              'ctaUrl' => 'explorar-clubes.php',
          ]); ?>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($meusClubesRecentes as $clube): ?>
              <?php
                $preset = 'preset-' . ($clube['cor_identidade'] ?? 'vinho');
                $lendoAgora = ($clube['ciclo_ativo_status'] ?? null) === 'em_andamento';
              ?>
              <div class="col-md-6">
                <article class="lp-club-card <?= e($preset) ?>">
                  <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <span class="d-inline-flex align-items-center gap-2 lp-meta-line">
                      <span class="lp-club-swatch" aria-hidden="true"></span>
                      <?= e((string) $clube['tema_nome']) ?>
                    </span>
                    <span class="lp-badge <?= $clube['papel'] === 'responsavel' ? 'lp-badge-burgundy' : 'lp-badge-moss' ?>">
                      <?= $clube['papel'] === 'responsavel' ? 'Responsável' : 'Membro' ?>
                    </span>
                  </div>

                  <h3 class="mb-4"><?= e((string) $clube['nome']) ?></h3>

                  <div class="lp-club-footer">
                    <span class="lp-club-status<?= $lendoAgora ? ' is-reading' : '' ?>">
                      <?= e($descreverCiclo($clube['ciclo_ativo_status'] ?? null, $clube['ciclo_ativo_livro'] ?? null)) ?>
                    </span>
                    <a class="lp-inline-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">
                      Abrir clube
                    </a>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

<?php else: ?>
  <!-- hero visitante -->
  <section class="lp-hero-open">
    <div class="lp-container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-5">
          <span class="lp-section-label lp-section-label-moss">Clubes de leitura</span>
          <h1 class="lp-hero-title">
            Histórias<br>aproximam.<br>Leituras<br>criam <em class="lp-hero-accent">laços.</em>
          </h1>
          <p class="lp-hero-copy">
            Clubes públicos e privados para escolher o próximo livro em votação, acompanhar o avanço no Kindle ou no papel e comentar cada capítulo sem spoilers.
          </p>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <a class="btn-lp-primary" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
            <a class="btn-lp-secondary" href="<?= e(url('quem-somos.php')) ?>">Nossa proposta</a>
          </div>
        </div>

        <div class="col-lg-7">
          <img
            class="lp-hero-photo"
            src="<?= e(url('assets/img/hero-mesa.jpg')) ?>"
            alt="Livros encadernados em pano vinho e verde-musgo sobre mesa de madeira ao lado de caderno aberto, caneta-tinteiro e café"
          >
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- acervo -->
<?php if ($livrosAcervo !== []): ?>
  <section class="lp-band-ivory" aria-labelledby="sec-acervo">
    <div class="lp-container">
      <div class="lp-band-header">
        <div>
          <span class="lp-section-label">Estante atual</span>
          <h2 id="sec-acervo" class="mt-1 mb-0">Livros no acervo</h2>
        </div>
        <a href="<?= e(url('acervo.php')) ?>" class="btn-lp-secondary btn-lp-sm">Ver todo o acervo</a>
      </div>

      <div class="row g-4">
        <?php foreach ($livrosAcervo as $idx => $livro): ?>
          <?php $qtdEdicoes = (int) ($livro['total_edicoes'] ?? 0); ?>
          <div class="col-6 col-md-3">
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
                <?php if ($qtdEdicoes > 0): ?>
                  &middot; <?= $qtdEdicoes ?> <?= $qtdEdicoes === 1 ? 'edição' : 'edições' ?>
                <?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- clubes abertos -->
<?php if ($clubesPublicos !== []): ?>
  <section class="lp-band-paper" aria-labelledby="sec-clubes">
    <div class="lp-container">
      <div class="lp-band-header">
        <div>
          <span class="lp-section-label lp-section-label-moss">Comunidades abertas</span>
          <h2 id="sec-clubes" class="mt-1 mb-0">Clubes para conhecer</h2>
        </div>
        <a href="<?= e(url('explorar-clubes.php')) ?>" class="btn-lp-secondary btn-lp-sm">Todos os clubes</a>
      </div>

      <div class="row g-4">
        <?php foreach ($clubesPublicos as $clube): ?>
          <?php
            $preset = 'preset-' . ($clube['cor_identidade'] ?? 'vinho');
            $totalMembros = (int) $clube['total_membros'];
            $lendoAgora = ($clube['ciclo_ativo_status'] ?? null) === 'em_andamento';
          ?>
          <div class="col-md-6">
            <article class="lp-club-card <?= e($preset) ?>">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <span class="d-inline-flex align-items-center gap-2 lp-meta-line">
                  <span class="lp-club-swatch" aria-hidden="true"></span>
                  <?= e((string) $clube['tema_nome']) ?>
                </span>
                <span class="lp-meta-line">
                  <?= $totalMembros ?> <?= $totalMembros === 1 ? 'leitor' : 'leitores' ?>
                </span>
              </div>

              <h3 class="mb-2"><?= e((string) $clube['nome']) ?></h3>
              <p class="lp-muted small mb-4">
                <?= e(mb_strimwidth((string) $clube['descricao'], 0, 145, '…', 'UTF-8')) ?>
              </p>

              <div class="lp-club-footer">
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
    </div>
  </section>
<?php endif; ?>

<?php if ($usuario === null): ?>
  <!-- caderno de margens -->
  <section class="lp-band-moss" aria-labelledby="sec-caderno">
    <div class="lp-container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <img
            class="lp-moss-photo"
            src="<?= e(url('assets/img/estante-biblioteca.jpg')) ?>"
            alt="Estantes de madeira escura com livros encadernados e luminária de latão sobre mesa de leitura"
            loading="lazy"
          >
        </div>

        <div class="col-lg-5 offset-lg-1">
          <span class="lp-section-label mb-2" style="color: var(--lp-brass);">Caderno de Margens</span>
          <h2 id="sec-caderno" class="mb-3">Cada leitor no seu ritmo, sem medo de spoilers.</h2>
          <p class="mb-3" style="color: rgba(251, 248, 241, 0.86);">
            Quem acompanha no Kindle informa a porcentagem; quem lê no livro físico escolhe a edição e informa a página. O sistema alinha ambos na mesma régua de 0 a 100%.
          </p>
          <p class="mb-4" style="color: rgba(251, 248, 241, 0.78);">
            Comentários e teorias adiante do ponto onde você está ficam protegidos no servidor até você avançar na obra — ou escolher revelar conscientemente.
          </p>

          <div class="d-flex flex-wrap align-items-center gap-3">
            <a class="btn-lp-light" href="<?= e(url('registro.php')) ?>">Criar conta gratuita</a>
            <a class="btn-lp-ghost-light" href="<?= e(url('quem-somos.php')) ?>">Como funciona</a>
          </div>

          <?php if ($leituraEmDestaque !== null): ?>
            <p class="small mt-4 mb-0 pt-3" style="border-top: 1px solid rgba(251, 248, 241, 0.16); color: rgba(243, 235, 221, 0.78);">
              Em leitura agora: <em><?= e((string) $leituraEmDestaque['ciclo_ativo_livro']) ?></em> no clube <?= e((string) $leituraEmDestaque['nome']) ?>.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- assuntos e convite -->
<section class="lp-band-ivory" aria-labelledby="sec-assuntos">
  <div class="lp-container">
    <?php if ($temas !== []): ?>
      <div class="row g-4 align-items-start">
        <div class="col-lg-4">
          <span class="lp-section-label lp-section-label-moss">Prateleiras</span>
          <h2 id="sec-assuntos" class="mt-1 mb-2">Explorar por assunto</h2>
          <p class="lp-muted small mb-0">
            Encontre clubes públicos organizados pelo gênero ou interesse literário que mais combina com a sua estante.
          </p>
        </div>

        <div class="col-lg-8">
          <div class="d-flex flex-wrap gap-2 pt-lg-2">
            <?php foreach ($temas as $tema): ?>
              <a class="lp-genre-pill" href="<?= e(url('explorar-clubes.php?tema=' . (int) $tema['id_tema'])) ?>">
                <?= e((string) $tema['nome']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="lp-closing-strip d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h3 class="mb-1">
          <?= $usuario === null ? 'Quer reunir amigos em torno de uma leitura?' : 'Quer abrir um novo clube de leitura?' ?>
        </h3>
        <p class="lp-muted small mb-0">
          Crie um clube público ou privado por convite, organize a votação do livro e acompanhe os encontros do grupo.
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2 flex-shrink-0">
        <?php if ($usuario === null): ?>
          <a class="btn-lp-moss" href="<?= e(url('registro.php')) ?>">Criar conta</a>
          <a class="btn-lp-secondary" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
        <?php else: ?>
          <a class="btn-lp-moss" href="<?= e(url('clube-form.php')) ?>">Criar clube</a>
          <a class="btn-lp-secondary" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
