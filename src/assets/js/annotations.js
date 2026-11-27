document.addEventListener('DOMContentLoaded', () => {
  // contador de caracteres do campo de anotação
  const textarea = document.getElementById('conteudo_anotacao');
  const contador = document.getElementById('contador_anotacao');
  if (textarea && contador) {
    const limite = parseInt(textarea.getAttribute('maxlength') || '2500', 10);
    const atualizarContador = () => {
      contador.textContent = `${textarea.value.length} / ${limite} caracteres`;
    };
    textarea.addEventListener('input', atualizarContador);
    atualizarContador();
  }

  // busca o texto do spoiler sob demanda quando o leitor confirma
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';

  document.querySelectorAll('[data-revelar-id]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const idAnotacao = btn.getAttribute('data-revelar-id') || '';
      const posicaoFmt = btn.getAttribute('data-posicao-fmt') || '';
      const apiUrl = btn.getAttribute('data-api-url') || 'api/revelar-anotacao.php';
      const container = btn.closest('[data-spoiler-card]');
      const feedback = container ? container.querySelector('[data-spoiler-feedback]') : null;

      if (!idAnotacao || !container) {
        return;
      }

      const confirmou = window.confirm(
        `Esta anotação está em ${posicaoFmt} da obra, à frente da sua leitura atual. Deseja revelar o texto mesmo assim?`
      );
      if (!confirmou) {
        return;
      }

      btn.disabled = true;
      const textoOriginal = btn.textContent;
      btn.textContent = 'Carregando trecho...';
      if (feedback) {
        feedback.textContent = '';
      }

      try {
        const body = new URLSearchParams();
        body.set('id_anotacao', idAnotacao);
        body.set('confirmado', '1');

        const resposta = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': csrfToken,
          },
          body: body.toString(),
          credentials: 'same-origin',
        });

        const payload = await resposta.json();
        if (!resposta.ok || !payload.ok) {
          throw new Error(payload.erro || 'Não foi possível revelar esta anotação agora.');
        }

        // monta o card só com textContent para evitar XSS
        container.classList.remove('lp-spoiler-entry');
        container.classList.add('lp-margin-note', `tipo-${payload.tipo}`);
        while (container.firstChild) {
          container.removeChild(container.firstChild);
        }

        const metaTopo = document.createElement('div');
        metaTopo.className = 'd-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2 lp-meta-line';

        const esquerda = document.createElement('span');
        const rotulo = document.createElement('strong');
        rotulo.textContent = payload.tipo_rotulo;
        esquerda.appendChild(rotulo);
        esquerda.appendChild(document.createTextNode(` · ${payload.posicao_fmt}`));
        if (payload.etapa_titulo) {
          esquerda.appendChild(document.createTextNode(` · ${payload.etapa_titulo}`));
        }

        const aviso = document.createElement('span');
        aviso.className = 'fst-italic';
        aviso.textContent = 'Revelado por você';

        metaTopo.appendChild(esquerda);
        metaTopo.appendChild(aviso);
        container.appendChild(metaTopo);

        if (payload.titulo) {
          const h3 = document.createElement('h3');
          h3.className = 'h4 mb-2';
          h3.textContent = payload.titulo;
          container.appendChild(h3);
        }

        const corpo = document.createElement('p');
        corpo.className = 'lp-editorial-prose mb-3';
        corpo.style.whiteSpace = 'pre-line';
        corpo.textContent = payload.conteudo;
        container.appendChild(corpo);

        const rodape = document.createElement('p');
        rodape.className = 'lp-meta-line small mb-0';
        rodape.textContent = `Por @${payload.autor_apelido} — ${payload.criado_em_fmt}`;
        container.appendChild(rodape);
      } catch (err) {
        btn.disabled = false;
        btn.textContent = textoOriginal;
        if (feedback) {
          feedback.textContent = err instanceof Error ? err.message : 'Erro ao conectar ao servidor.';
        }
      }
    });
  });
});
