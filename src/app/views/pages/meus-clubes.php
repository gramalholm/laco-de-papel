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
 * @var array $convites
 * @var array $responsavel
 * @var array $membro
 */

$descreverCiclo = static function (?string $status, ?string $livro): string {
    if ($status === 'em_andamento') {
        return $livro ? 'Lendo ' . $livro : 'Leitura em andamento';
    }
    if ($status === 'votacao') {
        return 'Votação aberta';
    }
    return 'Sem ciclo aberto';
};

$corSwatch = static function (?string $cor): string {
    return match ($cor) {
        'verde' => 'var(--lp-moss)',
        'madeira' => 'var(--lp-wood)',
        default => 'var(--lp-burgundy)',
    };
};

$semNada = ($convites === [] && $responsavel === [] && $membro === []);
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="lp-section-label lp-section-label-moss">Sua estante de comunidades</span>
        <h1 class="mt-1 mb-2">Meus clubes</h1>
        <p class="lp-muted mb-0">
          Clubes organizados por @<?= e($usuario['apelido']) ?>, grupos de que você participa e convites recebidos.
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn-lp-moss" href="<?= e(url('clube-form.php')) ?>">Criar clube</a>
        <a class="btn-lp-secondary" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
      </div>
    </div>

    <?php if ($convites !== []): ?>
      <div class="mt-5 pt-4" style="border-top: 1px solid var(--lp-border);" aria-labelledby="sec-convites">
        <span class="lp-section-label mb-2">Aguardando resposta</span>
        <h2 id="sec-convites" class="h3 mb-3">Convites recebidos</h2>

        <div class="lp-entry-list">
          <?php foreach ($convites as $convite): ?>
            <div class="lp-entry-row d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
              <div>
                <h3 class="mb-1"><?= e((string) $convite['nome']) ?></h3>
                <p class="lp-meta-line mb-0">
                  <?= e((string) $convite['tema_nome']) ?> &mdash; convite de <strong>@<?= e((string) ($convite['convidado_por_apelido'] ?? 'responsavel')) ?></strong>
                  <?php if (!empty($convite['convidado_em'])): ?>
                    em <?= e(formatarData((string) $convite['convidado_em'])) ?>
                  <?php endif; ?>
                </p>
              </div>
              <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <form method="POST" action="<?= e(url('actions/clubes/responder-convite.php')) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id_clube" value="<?= (int) $convite['id_clube'] ?>">
                  <input type="hidden" name="decisao" value="aceitar">
                  <button type="submit" class="btn-lp-moss btn-lp-sm">Aceitar convite</button>
                </form>
                <form method="POST" action="<?= e(url('actions/clubes/responder-convite.php')) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id_clube" value="<?= (int) $convite['id_clube'] ?>">
                  <input type="hidden" name="decisao" value="recusar">
                  <button type="submit" class="btn-lp-secondary btn-lp-sm">Recusar</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- clubes do leitor -->
<section class="lp-band-ivory">
  <div class="lp-container">
    <?php if ($semNada): ?>
      <?php View::component('empty-state', [
          'titulo' => 'Você ainda não participa de nenhum clube',
          'mensagem' => 'Entre em uma comunidade aberta ou crie seu próprio espaço para convidar amigos.',
          'ctaTexto' => 'Explorar clubes públicos',
          'ctaUrl' => 'explorar-clubes.php',
      ]); ?>
    <?php else: ?>
      <div class="mb-5 pb-3" aria-labelledby="sec-responsavel">
        <div class="d-flex justify-content-between align-items-baseline mb-4">
          <h2 id="sec-responsavel" class="h3 mb-0">Sou responsável</h2>
          <span class="lp-meta-line"><?= count($responsavel) ?> <?= count($responsavel) === 1 ? 'clube' : 'clubes' ?></span>
        </div>

        <?php if ($responsavel === []): ?>
          <p class="lp-muted small">
            Você ainda não administra nenhum clube. <a href="<?= e(url('clube-form.php')) ?>">Criar um clube</a>.
          </p>
        <?php else: ?>
          <div class="row g-5">
            <?php foreach ($responsavel as $clube): ?>
              <?php
                $arquivado = ($clube['status_clube'] ?? 'ativo') === 'arquivado';
                $lendoAgora = ($clube['ciclo_ativo_status'] ?? null) === 'em_andamento';
              ?>
              <div class="col-md-6">
                <article class="lp-club-entry">
                  <div class="d-flex justify-content-between align-items-center gap-2 mb-2 lp-meta-line">
                    <span class="d-inline-flex align-items-center gap-2">
                      <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color: <?= $corSwatch($clube['cor_identidade'] ?? null) ?>;" aria-hidden="true"></span>
                      <?= e((string) $clube['tema_nome']) ?>
                    </span>
                    <span>
                      <?= $clube['visibilidade'] === 'privado' ? 'Privado' : 'Público' ?>
                      <?= $arquivado ? ' · Arquivado' : '' ?>
                    </span>
                  </div>

                  <h3 class="mb-2">
                    <a class="lp-entry-title-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">
                      <?= e((string) $clube['nome']) ?>
                    </a>
                  </h3>
                  <p class="lp-muted small mb-4 flex-grow-1">
                    <?= e(mb_strimwidth((string) $clube['descricao'], 0, 145, '…', 'UTF-8')) ?>
                  </p>

                  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="lp-club-status<?= $lendoAgora ? ' is-reading' : '' ?>">
                      <?= e($descreverCiclo($clube['ciclo_ativo_status'] ?? null, $clube['ciclo_ativo_livro'] ?? null)) ?>
                    </span>
                    <div class="d-flex align-items-center gap-3">
                      <?php if (!$arquivado): ?>
                        <a class="small lp-muted text-decoration-none" href="<?= e(url('clube-form.php?id=' . (int) $clube['id_clube'])) ?>">Editar</a>
                      <?php endif; ?>
                      <a class="lp-inline-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">Abrir clube</a>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="pt-4" style="border-top: 1px solid var(--lp-border);" aria-labelledby="sec-membro">
        <div class="d-flex justify-content-between align-items-baseline mb-4">
          <h2 id="sec-membro" class="h3 mb-0">Faço parte</h2>
          <span class="lp-meta-line"><?= count($membro) ?> <?= count($membro) === 1 ? 'clube' : 'clubes' ?></span>
        </div>

        <?php if ($membro === []): ?>
          <p class="lp-muted small mb-0">
            Você ainda não participa como membro de outros clubes. <a href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes públicos</a>.
          </p>
        <?php else: ?>
          <div class="row g-5">
            <?php foreach ($membro as $clube): ?>
              <?php
                $arquivado = ($clube['status_clube'] ?? 'ativo') === 'arquivado';
                $lendoAgora = ($clube['ciclo_ativo_status'] ?? null) === 'em_andamento';
              ?>
              <div class="col-md-6">
                <article class="lp-club-entry">
                  <div class="d-flex justify-content-between align-items-center gap-2 mb-2 lp-meta-line">
                    <span class="d-inline-flex align-items-center gap-2">
                      <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color: <?= $corSwatch($clube['cor_identidade'] ?? null) ?>;" aria-hidden="true"></span>
                      <?= e((string) $clube['tema_nome']) ?>
                    </span>
                    <span>
                      <?= $clube['visibilidade'] === 'privado' ? 'Privado' : 'Público' ?>
                      <?= $arquivado ? ' · Arquivado' : '' ?>
                    </span>
                  </div>

                  <h3 class="mb-2">
                    <a class="lp-entry-title-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">
                      <?= e((string) $clube['nome']) ?>
                    </a>
                  </h3>
                  <p class="lp-muted small mb-4 flex-grow-1">
                    <?= e(mb_strimwidth((string) $clube['descricao'], 0, 145, '…', 'UTF-8')) ?>
                  </p>

                  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="lp-club-status<?= $lendoAgora ? ' is-reading' : '' ?>">
                      <?= e($descreverCiclo($clube['ciclo_ativo_status'] ?? null, $clube['ciclo_ativo_livro'] ?? null)) ?>
                    </span>
                    <a class="lp-inline-link" href="<?= e(url('clube.php?id=' . (int) $clube['id_clube'])) ?>">Abrir clube</a>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
