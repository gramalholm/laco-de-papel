<?php

declare(strict_types=1);

use App\Core\Csrf;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var bool $modoEdicao
 * @var array|null $clube
 * @var array $temas
 */

$valNome = old('nome', (string) ($clube['nome'] ?? ''));
$valTema = old('id_tema', (string) ($clube['id_tema'] ?? ''));
$valDescricao = old('descricao', (string) ($clube['descricao'] ?? ''));
$valRegras = old('regras', (string) ($clube['regras'] ?? ''));
$valVisibilidade = old('visibilidade', (string) ($clube['visibilidade'] ?? 'publico'));
$valCor = old('cor_identidade', (string) ($clube['cor_identidade'] ?? 'verde'));

$errNome = field_error('nome');
$errTema = field_error('id_tema');
$errDescricao = field_error('descricao');
$errRegras = field_error('regras');
$errVisibilidade = field_error('visibilidade');
$errCor = field_error('cor_identidade');
?>
<section class="lp-page-intro">
  <div class="lp-container">
    <p class="mb-3">
      <a class="lp-inline-link" href="<?= e(url($modoEdicao && $clube !== null ? 'clube.php?id=' . (int) $clube['id_clube'] : 'meus-clubes.php')) ?>">
        &larr; <?= $modoEdicao ? 'Voltar ao clube' : 'Meus clubes' ?>
      </a>
    </p>
    <span class="lp-section-label lp-section-label-moss">
      <?= $modoEdicao ? 'Configurações do clube' : 'Nova comunidade' ?>
    </span>
    <h1 class="mt-1 mb-2"><?= $modoEdicao ? 'Editar clube' : 'Abrir um clube de leitura' ?></h1>
    <p class="lp-muted mb-0" style="max-width: 56ch;">
      <?= $modoEdicao
          ? 'Ajuste o nome, o assunto, as regras ou a visibilidade da sua comunidade.'
          : 'Escolha o tema, a abertura do espaço e as combinações iniciais para reunir leitores.' ?>
    </p>
  </div>
</section>

<section class="lp-band-ivory">
  <div class="lp-container">
    <div class="row g-5 align-items-start">
      <aside class="col-lg-4">
        <h2 class="h3 mb-3">Sobre a organização</h2>
        <p class="lp-muted small mb-3">
          Quem abre um clube assume o papel de responsável naquele espaço, podendo iniciar ciclos, encerrar votações, cadastrar etapas de leitura e convidar pessoas pelo apelido.
        </p>
        <p class="lp-muted small mb-0">
          O papel vale apenas dentro deste clube — você continua participando livremente como membro em outras comunidades.
        </p>

        <?php if ($modoEdicao && $clube !== null): ?>
          <div class="mt-5 pt-4" style="border-top: 1px solid var(--lp-border);">
            <h3 class="h5 mb-2" style="color: var(--lp-error);">Arquivar este clube</h3>
            <p class="lp-muted small mb-3">
              Arquivar encerra novas entradas e edições, preservando todo o histórico de leituras e anotações do grupo.
            </p>
            <form
              method="POST"
              action="<?= e(url('actions/clubes/arquivar.php')) ?>"
              data-confirm="Tem certeza que deseja arquivar este clube? O histórico será mantido, mas novas ações ficarão bloqueadas."
            >
              <?= Csrf::field() ?>
              <input type="hidden" name="id_clube" value="<?= (int) $clube['id_clube'] ?>">
              <button type="submit" class="btn-lp-danger btn-lp-sm">Arquivar clube</button>
            </form>
          </div>
        <?php endif; ?>
      </aside>

      <div class="col-lg-7 offset-lg-1 lp-col-divider">
        <form method="POST" action="<?= e(url('actions/clubes/salvar.php')) ?>" novalidate>
          <?= Csrf::field() ?>
          <?php if ($modoEdicao && $clube !== null): ?>
            <input type="hidden" name="id_clube" value="<?= (int) $clube['id_clube'] ?>">
          <?php endif; ?>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="nome">Nome do clube</label>
            <input
              type="text"
              id="nome"
              name="nome"
              class="form-control lp-line-input<?= $errNome ? ' is-invalid' : '' ?>"
              value="<?= e($valNome) ?>"
              placeholder="Ex.: Leituras de Domingo"
              minlength="3"
              maxlength="120"
              required
            >
            <?php if ($errNome): ?>
              <div class="lp-field-error"><?= e($errNome) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="id_tema">Assunto literário</label>
            <select
              id="id_tema"
              name="id_tema"
              class="form-select lp-line-input<?= $errTema ? ' is-invalid' : '' ?>"
              required
            >
              <option value="">Selecione um assunto</option>
              <?php foreach ($temas as $tema): ?>
                <option value="<?= (int) $tema['id_tema'] ?>"<?= (string) $tema['id_tema'] === $valTema ? ' selected' : '' ?>>
                  <?= e((string) $tema['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($errTema): ?>
              <div class="lp-field-error"><?= e($errTema) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="descricao">Descrição</label>
            <textarea
              id="descricao"
              name="descricao"
              class="form-control lp-line-input<?= $errDescricao ? ' is-invalid' : '' ?>"
              rows="3"
              maxlength="800"
              placeholder="Conte que tipo de livros o clube lê e qual ritmo de encontro costuma seguir."
              required
            ><?= e($valDescricao) ?></textarea>
            <?php if ($errDescricao): ?>
              <div class="lp-field-error"><?= e($errDescricao) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-5">
            <label class="lp-field-label-caps" for="regras">Regras de convivência (opcional)</label>
            <textarea
              id="regras"
              name="regras"
              class="form-control lp-line-input<?= $errRegras ? ' is-invalid' : '' ?>"
              rows="2"
              maxlength="2000"
              placeholder="Ex.: Respeitar o ritmo de cada leitor e marcar a porcentagem correta nas anotações."
            ><?= e($valRegras) ?></textarea>
            <?php if ($errRegras): ?>
              <div class="lp-field-error"><?= e($errRegras) ?></div>
            <?php endif; ?>
          </div>

          <div class="row g-5 mb-5">
            <div class="col-md-6">
              <span class="lp-field-label-caps mb-2">Visibilidade</span>
              <label class="lp-choice-row">
                <input type="radio" name="visibilidade" value="publico" class="form-check-input mt-1"<?= $valVisibilidade === 'publico' ? ' checked' : '' ?>>
                <span>
                  <strong class="d-block">Clube público</strong>
                  <small class="lp-muted">Listado em Explorar clubes; qualquer leitor cadastrado pode entrar.</small>
                </span>
              </label>
              <label class="lp-choice-row">
                <input type="radio" name="visibilidade" value="privado" class="form-check-input mt-1"<?= $valVisibilidade === 'privado' ? ' checked' : '' ?>>
                <span>
                  <strong class="d-block">Clube privado</strong>
                  <small class="lp-muted">Fora das buscas públicas. Acesso somente mediante convite.</small>
                </span>
              </label>
              <?php if ($errVisibilidade): ?>
                <div class="lp-field-error"><?= e($errVisibilidade) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <span class="lp-field-label-caps mb-2">Cor de identidade</span>
              <label class="lp-choice-row align-items-center">
                <input type="radio" name="cor_identidade" value="verde" class="form-check-input mt-0"<?= $valCor === 'verde' ? ' checked' : '' ?>>
                <span class="lp-preset-dot verde" aria-hidden="true"></span>
                <span>Verde-musgo</span>
              </label>
              <label class="lp-choice-row align-items-center">
                <input type="radio" name="cor_identidade" value="vinho" class="form-check-input mt-0"<?= $valCor === 'vinho' ? ' checked' : '' ?>>
                <span class="lp-preset-dot vinho" aria-hidden="true"></span>
                <span>Vinho encadernado</span>
              </label>
              <label class="lp-choice-row align-items-center">
                <input type="radio" name="cor_identidade" value="madeira" class="form-check-input mt-0"<?= $valCor === 'madeira' ? ' checked' : '' ?>>
                <span class="lp-preset-dot madeira" aria-hidden="true"></span>
                <span>Madeira nogueira</span>
              </label>
              <?php if ($errCor): ?>
                <div class="lp-field-error"><?= e($errCor) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-3 align-items-center">
            <button type="submit" class="btn-lp-moss" data-loading-text="Salvando...">
              <?= $modoEdicao ? 'Salvar alterações' : 'Criar clube' ?>
            </button>
            <a class="lp-inline-link" href="<?= e(url($modoEdicao && $clube !== null ? 'clube.php?id=' . (int) $clube['id_clube'] : 'meus-clubes.php')) ?>">
              Cancelar
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
