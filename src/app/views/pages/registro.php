<?php

declare(strict_types=1);

use App\Core\Csrf;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

$errNome = field_error('nome');
$errApelido = field_error('apelido');
$errEmail = field_error('email');
$errSenha = field_error('senha');
$errConfirmar = field_error('confirmar_senha');
?>
<section class="lp-auth-split">
  <div class="lp-container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5 d-none d-lg-block">
        <img
          class="lp-auth-photo"
          src="<?= e(url('assets/img/estante-biblioteca.jpg')) ?>"
          alt="Estantes de madeira escura com livros encadernados e luminária sobre mesa de leitura"
        >
      </div>

      <div class="col-lg-5 offset-lg-1">
        <span class="lp-section-label lp-section-label-moss">Novo leitor</span>
        <h1 class="mt-2 mb-2">Criar sua conta.</h1>
        <p class="lp-muted mb-4">
          Participe de clubes públicos, receba convites para grupos privados pelo seu apelido e registre suas leituras.
        </p>

        <form method="POST" action="<?= e(url('actions/auth/registrar.php')) ?>" novalidate>
          <?= Csrf::field() ?>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="nome">Nome completo</label>
            <input
              type="text"
              id="nome"
              name="nome"
              class="form-control lp-line-input<?= $errNome ? ' is-invalid' : '' ?>"
              value="<?= e(old('nome')) ?>"
              placeholder="Ex.: Clara Mendes"
              maxlength="100"
              required
              autocomplete="name"
            >
            <?php if ($errNome): ?>
              <div class="lp-field-error"><?= e($errNome) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="apelido">Apelido (único)</label>
            <input
              type="text"
              id="apelido"
              name="apelido"
              class="form-control lp-line-input<?= $errApelido ? ' is-invalid' : '' ?>"
              value="<?= e(old('apelido')) ?>"
              placeholder="Ex.: clara_mendes"
              minlength="3"
              maxlength="40"
              required
              autocomplete="username"
            >
            <div class="lp-help-text">Usado por amigos para enviar convites de clubes privados.</div>
            <?php if ($errApelido): ?>
              <div class="lp-field-error"><?= e($errApelido) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label class="lp-field-label-caps" for="email">E-mail</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control lp-line-input<?= $errEmail ? ' is-invalid' : '' ?>"
              value="<?= e(old('email')) ?>"
              placeholder="voce@exemplo.com"
              maxlength="190"
              required
              autocomplete="email"
            >
            <?php if ($errEmail): ?>
              <div class="lp-field-error"><?= e($errEmail) ?></div>
            <?php endif; ?>
          </div>

          <div class="row g-4 mb-5">
            <div class="col-sm-6">
              <label class="lp-field-label-caps" for="senha">Senha</label>
              <input
                type="password"
                id="senha"
                name="senha"
                class="form-control lp-line-input<?= $errSenha ? ' is-invalid' : '' ?>"
                placeholder="Mínimo 8 caracteres"
                minlength="8"
                maxlength="128"
                required
                autocomplete="new-password"
              >
              <?php if ($errSenha): ?>
                <div class="lp-field-error"><?= e($errSenha) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-sm-6">
              <label class="lp-field-label-caps" for="confirmar_senha">Confirmar senha</label>
              <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                class="form-control lp-line-input<?= $errConfirmar ? ' is-invalid' : '' ?>"
                placeholder="Repita sua senha"
                minlength="8"
                maxlength="128"
                required
                autocomplete="new-password"
              >
              <?php if ($errConfirmar): ?>
                <div class="lp-field-error"><?= e($errConfirmar) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <button type="submit" class="btn-lp-moss" data-loading-text="Criando conta...">
              Criar minha conta
            </button>
            <a class="lp-inline-link" href="<?= e(url('login.php')) ?>">Já tenho cadastro</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
