<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\CicloService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

$usuario = Auth::exigirUsuario();
$idClube = (int) ($_POST['id_clube'] ?? 0);

if ($idClube <= 0) {
    Flash::erro('Clube inválido.');
    redirect('meus-clubes.php');
}

$urlRetorno = 'clube.php?id=' . $idClube;
$tituloCiclo = trim((string) ($_POST['titulo_ciclo'] ?? ''));
$dataLimite = trim((string) ($_POST['data_limite'] ?? ''));
$idLivro = (int) ($_POST['id_livro_escolhido'] ?? 0);

$validator = new Validator();
if ($tituloCiclo !== '') {
    $validator->tamanho('titulo_ciclo', $tituloCiclo, 2, 120, 'Título do ciclo');
}
if ($dataLimite !== '') {
    $validator->dataValida('data_limite', $dataLimite, 'Data limite');
}

if ($validator->falhou()) {
    Flash::erro($validator->primeiroErro() ?? 'Dados inválidos para iniciar o ciclo.');
    redirect($urlRetorno);
}

// Sem livro escolhido, o ciclo abre em votação; com livro definido, já inicia em andamento (máximo de 1 aberto por clube)
try {
    $service = new CicloService();
    $idCiclo = $service->criarCiclo($idClube, $usuario['id_usuario'], [
        'titulo_ciclo' => $tituloCiclo,
        'data_limite' => $dataLimite,
        'id_livro_escolhido' => $idLivro,
    ]);

    Flash::sucesso(
        $idLivro > 0
            ? 'Novo ciclo iniciado! Os participantes já podem registrar o progresso da leitura.'
            : 'Votação aberta! Os participantes já podem indicar livros do acervo e votar.'
    );
    redirect('ciclo.php?id=' . $idCiclo);
} catch (DomainException $e) {
    Flash::erro($e->getMessage());
    redirect($urlRetorno);
}
