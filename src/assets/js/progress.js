// prévia local do progresso (Kindle ou físico); o cálculo oficial continua no PHP

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-progresso');
  if (!form) {
    return;
  }

  const radiosFormato = form.querySelectorAll('input[name="formato"]');
  const blocoKindle = document.getElementById('campos-kindle');
  const blocoFisico = document.getElementById('campos-fisico');
  const inputKindle = document.getElementById('percentual_kindle');
  const selectEdicao = document.getElementById('id_edicao');
  const inputPagina = document.getElementById('pagina_atual');
  const previewTexto = document.getElementById('progresso-preview-texto');
  const previewBarra = document.getElementById('progresso-preview-barra');

  const obterFormatoAtual = () => {
    const marcado = form.querySelector('input[name="formato"]:checked');
    return marcado ? marcado.value : 'kindle';
  };

  const formatarPct = (valor) => {
    const arredondado = Math.round(valor * 100) / 100;
    const texto = Number.isInteger(arredondado)
      ? String(arredondado)
      : arredondado.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return `${texto.replace('.', ',')}%`;
  };

  const sincronizarLimitePagina = () => {
    if (!inputPagina) {
      return 0;
    }
    const option = selectEdicao?.options[selectEdicao.selectedIndex];
    const totalPaginas = parseInt(option?.getAttribute('data-paginas') || '0', 10);
    if (totalPaginas > 0) {
      inputPagina.max = String(totalPaginas);
      return totalPaginas;
    }
    inputPagina.removeAttribute('max');
    return 0;
  };

  const atualizarPreview = () => {
    const formato = obterFormatoAtual();
    let pct = 0;
    let detalhe = '';

    if (formato === 'kindle') {
      const kindleStr = (inputKindle?.value || '').trim();
      const val = parseFloat(kindleStr.replace(',', '.'));
      if (kindleStr !== '' && Number.isFinite(val)) {
        pct = Math.max(0, Math.min(100, val));
        detalhe = `Kindle · ${formatarPct(pct)}`;
      } else {
        detalhe = 'Informe sua porcentagem atual no Kindle (0 a 100)';
      }
    } else {
      const totalPaginas = sincronizarLimitePagina();
      const pagStr = (inputPagina?.value || '').trim();
      const pagAtual = parseInt(pagStr, 10);

      if (totalPaginas > 0 && pagStr !== '' && Number.isFinite(pagAtual) && pagAtual >= 0) {
        const clampedPag = Math.min(totalPaginas, pagAtual);
        pct = Math.max(0, Math.min(100, (clampedPag / totalPaginas) * 100));
        detalhe = `Página ${clampedPag} de ${totalPaginas} · ~${formatarPct(pct)}`;
      } else {
        detalhe = 'Selecione uma edição física e informe a página atual';
      }
    }

    if (previewTexto) {
      previewTexto.textContent = detalhe;
    }
    if (previewBarra) {
      previewBarra.style.width = `${pct.toFixed(2)}%`;
    }
  };

  const alternarCampos = (atualizarTexto = true) => {
    const ehKindle = obterFormatoAtual() === 'kindle';
    blocoKindle?.classList.toggle('d-none', !ehKindle);
    blocoFisico?.classList.toggle('d-none', ehKindle);

    if (inputKindle) {
      inputKindle.disabled = !ehKindle;
    }
    if (selectEdicao) {
      selectEdicao.disabled = ehKindle;
    }
    if (inputPagina) {
      inputPagina.disabled = ehKindle;
    }

    if (atualizarTexto) {
      atualizarPreview();
    } else if (!ehKindle) {
      sincronizarLimitePagina();
    }
  };

  radiosFormato.forEach((r) => r.addEventListener('change', () => alternarCampos(true)));
  inputKindle?.addEventListener('input', atualizarPreview);
  selectEdicao?.addEventListener('change', atualizarPreview);
  inputPagina?.addEventListener('input', atualizarPreview);

  // Na carga inicial preserva o resumo vindo do servidor (data/editora/aviso anti-spoiler)
  alternarCampos(false);
});
