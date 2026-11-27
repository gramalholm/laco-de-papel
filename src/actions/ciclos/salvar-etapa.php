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

Auth::exigirUsuario();
$idCiclo = (int) ($_POST['id_ciclo'] ?? 0);
$idEtapa = (int) ($_POST['id_etapa'] ?? 0);

if ($idCiclo <= 0) {
    Flash::erro('Ciclo inválido.');
    redirect('meus-clubes.php');
}

// Atende tanto nova etapa quanto edição, ajustando o retorno em caso de erro de validação
$urlRetorno = $idEtapa > 0 ? 'atualizar.php?tipo=etapa&id=' . $idEtapa : 'ciclo.php?id=' . $idCiclo . '#etapas';

$ordem = trim((string) ($_POST['ordem'] ?? ''));
$titulo = trim((string) ($_POST['titulo'] ?? ''));
$pergunta = trim((string) ($_POST['pergunta'] ?? ''));
$percentualAlvo = str_replace(',', '.', trim((string) ($_POST['percentual_alvo'] ?? '')));
$dataLimite = trim((string) ($_POST['data_limite'] ?? ''));

$validator = (new Validator())
    ->obrigatorio('titulo', $titulo, 'Título da etapa')
    ->tamanho('titulo', $titulo, 3, 140, 'Título da etapa')
    ->obrigatorio('percentual_alvo', $percentualAlvo, 'Percentual-alvo')
    ->decimal('percentual_alvo', $percentualAlvo, 0.0, 100.0, 'Percentual-alvo');

if ($ordem !== '') {
    $validator->inteiro('ordem', $ordem, 1, 50, 'Ordem');
}
if ($pergunta !== '') {
    $validator->tamanho('pergunta', $pergunta, 3, 700, 'Pergunta de discussão');
}
if ($dataLimite !== '') {
    $validator->dataValida('data_limite', $dataLimite, 'Data limite');
}

if ($validator->falhou()) {
    if ($idEtapa > 0) {
        Flash::setErrors($validator->erros());
    }
    Flash::setOld($_POST);
    Flash::erro($validator->primeiroErro() ?? 'Revise os dados da etapa.');
    redirect($urlRetorno);
}

// Quando a ordem vem vazia, a CicloService calcula automaticamente a próxima posição livre no ciclo
try {
    $service = new CicloService();
    $service->salvarEtapa($idCiclo, [
        'ordem' => $ordem !== '' ? (int) $ordem : 0,
        'titulo' => $titulo,
        'pergunta' => $pergunta,
        'percentual_alvo' => (float) $percentualAlvo,
        'data_limite' => $dataLimite,
    ], $idEtapa > 0 ? $idEtapa : null);

    Flash::sucesso($idEtapa > 0 ? 'Etapa atualizada.' : 'Etapa de leitura adicionada ao ciclo.');
    redirect('ciclo.php?id=' . $idCiclo . '#etapas');
} catch (DomainException $e) {
    Flash::setOld($_POST);
    Flash::erro($e->getMessage());
    redirect($urlRetorno);
}
