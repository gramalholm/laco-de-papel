<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ClubeService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

$usuario = Auth::exigirUsuario();

// Mesmo handler para criar e editar; a presença de id_clube define o modo e o destino de retorno
$idClube = (int) ($_POST['id_clube'] ?? 0);
$modoEdicao = $idClube > 0;
$urlRetorno = $modoEdicao ? 'clube-form.php?id=' . $idClube : 'clube-form.php';

$nome = trim((string) ($_POST['nome'] ?? ''));
$idTema = (int) ($_POST['id_tema'] ?? 0);
$descricao = trim((string) ($_POST['descricao'] ?? ''));
$regras = trim((string) ($_POST['regras'] ?? ''));
$visibilidade = trim((string) ($_POST['visibilidade'] ?? 'publico'));
$corIdentidade = trim((string) ($_POST['cor_identidade'] ?? 'vinho'));

$validator = (new Validator())
    ->obrigatorio('nome', $nome, 'Nome do clube')
    ->tamanho('nome', $nome, 3, 120, 'Nome do clube')
    ->obrigatorio('descricao', $descricao, 'Descrição')
    ->tamanho('descricao', $descricao, 10, 800, 'Descrição')
    ->emLista('visibilidade', $visibilidade, ['publico', 'privado'], 'Visibilidade')
    ->emLista('cor_identidade', $corIdentidade, ['vinho', 'verde', 'madeira'], 'Cor de identidade');

if ($idTema <= 0) {
    $validator->adicionarErro('id_tema', 'Selecione um assunto literário para o clube.');
}

if ($regras !== '') {
    $validator->tamanho('regras', $regras, 3, 2000, 'Regras de convivência');
}

if ($validator->falhou()) {
    Flash::setErrors($validator->erros());
    Flash::setOld($_POST);
    Flash::erro('Revise os campos destacados antes de salvar o clube.');
    redirect($urlRetorno);
}

$service = new ClubeService();

try {
    $payload = [
        'nome' => $nome,
        'id_tema' => $idTema,
        'descricao' => $descricao,
        'regras' => $regras,
        'visibilidade' => $visibilidade,
        'cor_identidade' => $corIdentidade,
    ];

    // Na edição valida se o usuário é responsável; na criação já vincula o criador como responsável ativo
    if ($modoEdicao) {
        $service->editarClube($idClube, $payload);
        Flash::sucesso('As informações do clube foram atualizadas.');
        redirect('clube.php?id=' . $idClube);
    }

    $novoId = $service->criarClube($usuario['id_usuario'], $payload);
    Flash::sucesso('Clube criado! Agora você pode iniciar o primeiro ciclo ou convidar participantes.');
    redirect('clube.php?id=' . $novoId);
} catch (DomainException $e) {
    Flash::setOld($_POST);
    Flash::erro($e->getMessage());
    redirect($urlRetorno);
}
