<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}
?>
<article>
  <section class="lp-hero-open">
    <div class="lp-container">
      <div class="mb-5" style="max-width: 720px;">
        <span class="lp-section-label lp-section-label-moss">Quem somos</span>
        <h1 class="mt-2 mb-3">Uma casa tranquila para ler em companhia.</h1>
        <p class="lp-muted fs-5 mb-0">
          O Laço de Papel nasceu para dar espaço organizado aos clubes de leitura independentes — sem pressa, sem competição e sem spoilers.
        </p>
      </div>

      <img
        class="lp-about-photo"
        src="<?= e(url('assets/img/estante-biblioteca.jpg')) ?>"
        alt="Estantes de madeira escura com livros encadernados e luminária acesa sobre mesa de leitura"
      >
    </div>
  </section>

  <section class="lp-band-ivory">
    <div class="lp-container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-7">
          <div class="lp-editorial-prose">
            <p class="lp-dropcap">
              Quem já participou de um clube de leitura conhece o roteiro: a escolha do próximo título se perde no meio de dezenas de mensagens, metade das pessoas lê no Kindle enquanto a outra metade lê em edições físicas com paginações diferentes, e basta um comentário antecipado para revelar uma virada importante a quem ainda estava nos primeiros capítulos.
            </p>
            <p>
              No <strong>Laço de Papel</strong>, cada clube funciona como uma sala reservada em torno de um livro por vez. O grupo indica obras do acervo compartilhado, escolhe a próxima leitura por votação, combina etapas e conversa no Caderno de Margens respeitando o ponto exato onde cada pessoa está.
            </p>
            <p class="lp-muted mb-0">
              Não existem rankings de velocidade nem recomendações algorítmicas. A curadoria acontece nas mãos dos leitores que mantêm seus clubes vivos.
            </p>
          </div>
        </div>

        <aside class="col-lg-4 offset-lg-1 pt-lg-2">
          <blockquote class="lp-pullquote mb-3">
            “Histórias aproximam. Leituras criam laços.”
          </blockquote>
          <p class="lp-colophon-mark mb-4" style="font-size: 1.15rem;">
            Laço de Papel
          </p>
          <p class="lp-muted small mb-4">
            Um ciclo por vez: indicação, votação, progresso no seu formato e encontro para conversar sobre a obra.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn-lp-primary btn-lp-sm" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
            <a class="btn-lp-secondary btn-lp-sm" href="<?= e(url('registro.php')) ?>">Criar conta</a>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <!-- princípios -->
  <section class="lp-band-moss" aria-labelledby="sec-principios">
    <div class="lp-container">
      <div class="mb-5">
        <span class="lp-section-label mb-2" style="color: var(--lp-brass);">Escolhas de projeto</span>
        <h2 id="sec-principios" class="mb-0">O que guia a leitura por aqui</h2>
      </div>

      <div>
        <div class="row g-3 lp-moss-row">
          <div class="col-md-4">
            <h3 class="mb-0">Comunidades independentes</h3>
          </div>
          <div class="col-md-8">
            <p class="mb-0" style="color: rgba(251, 248, 241, 0.82);">
              Um mesmo leitor pode participar de quantos clubes quiser. Cada comunidade possui seus próprios participantes, assunto literário, ciclos e agenda de encontros.
            </p>
          </div>
        </div>

        <div class="row g-3 lp-moss-row">
          <div class="col-md-4">
            <h3 class="mb-0">Leitura sem competição</h3>
          </div>
          <div class="col-md-8">
            <p class="mb-0" style="color: rgba(251, 248, 241, 0.82);">
              Não há placares nem medalhas por terminar primeiro. Registrar o ponto em que você está serve apenas para situar a conversa coletiva e manter o ritmo confortável.
            </p>
          </div>
        </div>

        <div class="row g-3 lp-moss-row">
          <div class="col-md-4">
            <h3 class="mb-0">Kindle e livro físico juntos</h3>
          </div>
          <div class="col-md-8">
            <p class="mb-0" style="color: rgba(251, 248, 241, 0.82);">
              Leitores de e-book informam a porcentagem; leitores de edição impressa escolhem sua edição no acervo e informam a página atual. Ambos aparecem na mesma escala de 0 a 100%.
            </p>
          </div>
        </div>

        <div class="row g-3 lp-moss-row">
          <div class="col-md-4">
            <h3 class="mb-0">Proteção real contra spoilers</h3>
          </div>
          <div class="col-md-8">
            <p class="mb-0" style="color: rgba(251, 248, 241, 0.82);">
              Anotações marcadas à frente do seu ponto na leitura não são enviadas no HTML inicial da página. O texto só sai do servidor quando você alcança aquela marca ou decide revelar conscientemente.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- clubes públicos e privados -->
  <section class="lp-band-paper" aria-labelledby="sec-visibilidade">
    <div class="lp-container">
      <div class="row g-5 mb-5">
        <div class="col-md-6">
          <span class="lp-section-label lp-section-label-moss mb-2">Acesso aberto</span>
          <h2 id="sec-visibilidade" class="h3 mb-2">Clubes públicos</h2>
          <p class="lp-muted mb-0">
            Aparecem em <em>Explorar clubes</em> organizados por assunto. Qualquer leitor cadastrado pode entrar diretamente, votar na escolha dos livros e participar do Caderno de Margens.
          </p>
        </div>

        <div class="col-md-6">
          <span class="lp-section-label mb-2">Acesso por convite</span>
          <h2 class="h3 mb-2">Clubes privados</h2>
          <p class="lp-muted mb-0">
            Ficam fora das buscas públicas e invisíveis para quem não participa. O responsável envia convites pelo apelido cadastrado, preservando um ambiente reservado para amigos ou turmas.
          </p>
        </div>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-3 pt-4" style="border-top: 1px solid var(--lp-border);">
        <a class="btn-lp-primary" href="<?= e(url('explorar-clubes.php')) ?>">Explorar clubes</a>
        <a class="btn-lp-secondary" href="<?= e(url('registro.php')) ?>">Criar conta gratuita</a>
      </div>
    </div>
  </section>
</article>
