document.addEventListener('DOMContentLoaded', () => {
  // mostra os campos da edição física apenas quando o checkbox estiver marcado
  const checkboxEdicao = document.getElementById('incluir_edicao');
  const blocoEdicao = document.getElementById('bloco-primeira-edicao');

  if (checkboxEdicao && blocoEdicao) {
    const atualizarVisibilidade = () => {
      blocoEdicao.classList.toggle('d-none', !checkboxEdicao.checked);
    };
    checkboxEdicao.addEventListener('change', atualizarVisibilidade);
    atualizarVisibilidade();
  }

  // normaliza ISBN removendo espaços e hífens ao sair do campo
  document.querySelectorAll('input[data-clean-isbn="true"]').forEach((input) => {
    input.addEventListener('blur', () => {
      input.value = input.value.replace(/[\s-]+/g, '').toUpperCase();
    });
  });
});
