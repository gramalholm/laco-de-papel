document.addEventListener('DOMContentLoaded', () => {
  // aplica o filtro de assunto assim que o select muda
  const filtroTema = document.querySelector('[data-auto-submit="true"]');
  if (filtroTema && filtroTema.form) {
    filtroTema.addEventListener('change', () => {
      filtroTema.form.submit();
    });
  }

  // remove @ inicial caso o usuário cole ou digite @apelido
  const inputApelido = document.querySelector('input[data-clean-handle="true"]');
  if (inputApelido) {
    inputApelido.addEventListener('blur', () => {
      inputApelido.value = inputApelido.value.trim().replace(/^@+/, '');
    });
  }
});
