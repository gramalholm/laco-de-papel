<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * @var string|null $pageTitle
 * @var string|null $activeNav
 */

$pageTitle = (string) ($pageTitle ?? 'Laço de Papel — Histórias aproximam. Leituras criam laços.');
$activeNav = (string) ($activeNav ?? '');
$usuarioLogado = Auth::user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/img/favicon.svg')) ?>">
  <?= Csrf::metaTag() . "\n" ?>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Alegreya+Sans:ital,wght@0,400;0,500;0,700;1,400&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <link rel="stylesheet" href="<?= e(url('assets/css/tokens.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/components.css?v=2')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages.css')) ?>">
</head>
<body>
<header class="lp-header">
  <nav class="navbar navbar-expand-lg lp-navbar" aria-label="Navegação principal">
    <div class="lp-container container-fluid px-3 px-lg-4">
      <a class="lp-brand" href="<?= e(url('index.php')) ?>">
        <img src="<?= e(url('assets/img/logo-laco.svg')) ?>" alt="" width="28" height="28">
        <span class="lp-brand-name">Laço de Papel</span>
      </a>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#lpMainNav"
        aria-controls="lpMainNav"
        aria-expanded="false"
        aria-label="Abrir navegação"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="lpMainNav">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4">
          <li class="nav-item">
            <a class="lp-nav-link nav-link<?= $activeNav === 'inicio' ? ' is-active' : '' ?>" href="<?= e(url('index.php')) ?>">Início</a>
          </li>
          <li class="nav-item">
            <a class="lp-nav-link nav-link<?= $activeNav === 'explorar' ? ' is-active' : '' ?>" href="<?= e(url('explorar-clubes.php')) ?>">Clubes</a>
          </li>
          <?php if ($usuarioLogado !== null): ?>
            <li class="nav-item">
              <a class="lp-nav-link nav-link<?= $activeNav === 'meus-clubes' ? ' is-active' : '' ?>" href="<?= e(url('meus-clubes.php')) ?>">Meus clubes</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="lp-nav-link nav-link<?= $activeNav === 'acervo' ? ' is-active' : '' ?>" href="<?= e(url('acervo.php')) ?>">Acervo</a>
          </li>
          <?php if ($usuarioLogado !== null): ?>
            <li class="nav-item">
              <a class="lp-nav-link nav-link<?= $activeNav === 'estante' ? ' is-active' : '' ?>" href="<?= e(url('minha-estante.php')) ?>">Estante</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="lp-nav-link nav-link<?= $activeNav === 'quem-somos' ? ' is-active' : '' ?>" href="<?= e(url('quem-somos.php')) ?>">Quem somos</a>
            </li>
          <?php endif; ?>
        </ul>

        <div class="lp-nav-actions d-flex flex-wrap align-items-center gap-3">
          <?php if ($usuarioLogado !== null): ?>
            <a class="lp-nav-enter" href="<?= e(url('clube-form.php')) ?>">Criar clube</a>
            <div class="dropdown">
              <button
                class="lp-user-pill dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                @<?= e($usuarioLogado['apelido']) ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-1">
                  <small class="lp-muted d-block"><?= e($usuarioLogado['nome']) ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(url('atualizar.php')) ?>">Atualizar registros</a></li>
                <li><a class="dropdown-item" href="<?= e(url('excluir.php')) ?>">Excluir ou arquivar</a></li>
                <li><a class="dropdown-item" href="<?= e(url('quem-somos.php')) ?>">Quem somos</a></li>
                <li><hr class="dropdown-divider"></li>
                <li class="px-1">
                  <form method="POST" action="<?= e(url('actions/auth/sair.php')) ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="dropdown-item text-danger fw-semibold">Sair</button>
                  </form>
                </li>
              </ul>
            </div>
          <?php else: ?>
            <a class="lp-nav-enter" href="<?= e(url('login.php')) ?>">Entrar</a>
            <a class="btn-lp-secondary btn-lp-sm" href="<?= e(url('registro.php')) ?>">Criar conta</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</header>
