document.addEventListener('DOMContentLoaded', () => {
  // evita clique duplo enquanto o POST processa (validação oficial continua no PHP)
  document.querySelectorAll('form[method="post"], form[method="POST"]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      // confirmação opcional via data-confirm
      const confirmMsg = form.getAttribute('data-confirm');
      if (confirmMsg && !window.confirm(confirmMsg)) {
        event.preventDefault();
        return;
      }

      if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
      }

      form.dataset.submitting = 'true';
      const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
      submitButtons.forEach((btn) => {
        btn.disabled = true;
        if (btn.tagName === 'BUTTON' && !btn.dataset.originalText) {
          btn.dataset.originalText = btn.textContent;
          btn.textContent = btn.getAttribute('data-loading-text') || 'Enviando...';
        }
      });
    });
  });

  // troca para capa padrão se a imagem externa falhar (inclusive se já falhou antes do listener)
  document.querySelectorAll('img[data-fallback-src]').forEach((img) => {
    const aplicarFallback = () => {
      const fallback = img.getAttribute('data-fallback-src');
      if (fallback && img.src !== fallback) {
        img.src = fallback;
      }
    };

    img.addEventListener('error', aplicarFallback, { once: true });
    if (img.complete && img.naturalWidth === 0) {
      aplicarFallback();
    }
  });
});

// reativa botões se o navegador restaurar a página pelo histórico (bfcache)
window.addEventListener('pageshow', () => {
  document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
    delete form.dataset.submitting;
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
      btn.disabled = false;
      if (btn.tagName === 'BUTTON' && btn.dataset.originalText) {
        btn.textContent = btn.dataset.originalText;
        delete btn.dataset.originalText;
      }
    });
  });
});
