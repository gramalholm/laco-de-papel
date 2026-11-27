<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\LeituraService;

exigirPost('meus-clubes.php');
Csrf::exigirValido('meus-clubes.php');

$usuario = Auth::exigirUsuario();
$idCiclo = (int) ($_POST['id_ciclo'] ?? 0);
$idAnotacao = (int) ($_POST['id_anotacao'] ?? 0);

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

// Mesmo endpoint atende criação e edição; o retorno varia conforme a origem do formulário
$urlRetorno = $idAnotacao > 0
    ? 'atualizar.php?tipo=anotacao&id=' . $idAnotacao
    : 'caderno.php?id=' . $idCiclo . '&nova=1';

$tipo = trim((string) ($_POST['tipo'] ?? ''));
$titulo = trim((string) ($_POST['titulo'] ?? ''));
$conteudo = trim((string) ($_POST['conteudo'] ?? ''));
$posicaoPct = str_replace(',', '.', trim((string) ($_POST['posicao_pct'] ?? '')));
$idEtapa = (int) ($_POST['id_etapa'] ?? 0);

$validator = (new Validator())
    ->emLista('tipo', $tipo, ['grifo', 'duvida', 'teoria', 'reflexao', 'comentario'], 'Tipo de anotação')
    ->obrigatorio('conteudo', $conteudo, 'Conteúdo')
    ->tamanho('conteudo', $conteudo, 3, 2500, 'Conteúdo')
    ->obrigatorio('posicao_pct', $posicaoPct, 'Posição na obra (%)')
    ->decimal('posicao_pct', $posicaoPct, 0.0, 100.0, 'Posição na obra (%)');

if ($titulo !== '') {
    $validator->tamanho('titulo', $titulo, 2, 160, 'Título');
}

if ($validator->falhou()) {
    Flash::setErrors($validator->erros());
    Flash::setOld($_POST);
    Flash::erro($validator->primeiroErro() ?? 'Revise os campos da anotação.');
    redirect($urlRetorno);
}

// A validação de domínio garante que a posição da anotação não ultrapasse o progresso atual do leitor, preservando a lógica anti-spoiler
try {
    $service = new LeituraService();
    $service->salvarAnotacao($idCiclo, $usuario['id_usuario'], [
        'tipo' => $tipo,
        'titulo' => $titulo,
        'conteudo' => $conteudo,
        'posicao_pct' => (float) $posicaoPct,
        'id_etapa' => $idEtapa > 0 ? $idEtapa : null,
    ], $idAnotacao > 0 ? $idAnotacao : null);

    Flash::sucesso($idAnotacao > 0 ? 'Anotação atualizada.' : 'Anotação registrada no Caderno de Margens.');
    redirect('caderno.php?id=' . $idCiclo);
} catch (DomainException $e) {
    Flash::setOld($_POST);
    Flash::erro($e->getMessage());
    redirect($urlRetorno);
}
