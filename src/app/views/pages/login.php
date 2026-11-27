<?php

declare(strict_types=1);

use App\Core\Csrf;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

$errEmail = field_error('email');
$errSenha = field_error('senha');
?>
<section class="lp-auth-split">
  <div class="lp-container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5 d-none d-lg-block">
        <img
          class="lp-auth-photo"
          src="<?= e(url('assets/img/hero-mesa.jpg')) ?>"
          alt="Livros encadernados sobre mesa de madeira junto a um caderno de anotações e xícara de café"
        >
      </div>

      <div class="col-lg-5 offset-lg-1">
        <span class="lp-section-label lp-section-label-moss">Acesso do leitor</span>
        <h1 class="mt-2 mb-2">Bem-vindo de volta.</h1>
        <p class="lp-muted mb-5">
          Entre com seu e-mail para retomar seus clubes, atualizar leituras e abrir o Caderno de Margens.
        </p>

        <form method="POST" action="<?= e(url('actions/auth/entrar.php')) ?>" novalidate>
          <?= Csrf::field() ?>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="email">E-mail</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control lp-line-input<?= $errEmail ? ' is-invalid' : '' ?>"
              value="<?= e(old('email')) ?>"
              placeholder="voce@exemplo.com"
              required
              autocomplete="email"
            >
            <?php if ($errEmail): ?>
              <div class="lp-field-error"><?= e($errEmail) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-5">
            <label class="lp-field-label-caps" for="senha">Senha</label>
            <input
              type="password"
              id="senha"
              name="senha"
              class="form-control lp-line-input<?= $errSenha ? ' is-invalid' : '' ?>"
              placeholder="Digite sua senha"
              required
              autocomplete="current-password"
            >
            <?php if ($errSenha): ?>
              <div class="lp-field-error"><?= e($errSenha) ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <button type="submit" class="btn-lp-primary" data-loading-text="Entrando...">
              Entrar na conta
            </button>
            <a class="lp-inline-link" href="<?= e(url('registro.php')) ?>">Ainda não tenho conta</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
