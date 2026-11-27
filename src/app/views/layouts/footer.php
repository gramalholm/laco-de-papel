<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/** @var array|null $scripts */

$scripts = isset($scripts) && is_array($scripts) ? $scripts : [];
?>
<footer class="lp-footer">
  <div class="lp-container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-6">
        <p class="lp-footer-wordmark mb-2">Laço de Papel</p>
        <p class="lp-footer-slogan">Histórias aproximam. Leituras criam laços.</p>
        <p class="lp-footer-note mb-0">
          Comunidade de clubes de leitura públicos e privados, pensada para acompanhar cada obra sem pressa e proteger as conversas de quem ainda está chegando.
        </p>
      </div>

      <div class="col-6 col-lg-3">
        <p class="lp-footer-heading">Navegação</p>
        <ul class="list-unstyled lp-footer-list mb-0">
          <li><a href="<?= e(url('index.php')) ?>">Início</a></li>
          <li><a href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a></li>
          <li><a href="<?= e(url('acervo.php')) ?>">Acervo de livros</a></li>
          <li><a href="<?= e(url('quem-somos.php')) ?>">Quem somos</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-3">
        <p class="lp-footer-heading">Leitor</p>
        <ul class="list-unstyled lp-footer-list mb-0">
          <?php if (\App\Core\Auth::check()): ?>
            <li><a href="<?= e(url('meus-clubes.php')) ?>">Meus clubes</a></li>
            <li><a href="<?= e(url('minha-estante.php')) ?>">Minha estante</a></li>
            <li><a href="<?= e(url('clube-form.php')) ?>">Abrir um clube</a></li>
            <li><a href="<?= e(url('atualizar.php')) ?>">Atualizar registros</a></li>
          <?php else: ?>
            <li><a href="<?= e(url('login.php')) ?>">Entrar na conta</a></li>
            <li><a href="<?= e(url('registro.php')) ?>">Criar conta gratuita</a></li>
            <li><a href="<?= e(url('explorar-clubes.php')) ?>">Encontrar um clube</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="lp-footer-colophon d-flex flex-column flex-sm-row justify-content-between gap-2">
      <span>&copy; <?= date('Y') ?> Laço de Papel.</span>
      <span>Leitura compartilhada, no seu tempo.</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(url('assets/js/forms.js?v=3')) ?>"></script>
<?php foreach ($scripts as $scriptPath): ?>
  <script src="<?= e(url($scriptPath)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
