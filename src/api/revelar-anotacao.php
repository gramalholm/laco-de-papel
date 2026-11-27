<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Services\LeituraService;

// Revela sob demanda anotações ocultadas por spoiler, sem expor o conteúdo no HTML inicial.
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'erro' => 'Método não permitido. Use POST.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

Auth::exigirUsuario(true);
Csrf::exigirValido(null, true);

// Aceita formulário ou JSON no corpo para suportar diferentes chamadas via fetch
$dados = $_POST;
if ($dados === []) {
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $dados = $json;
        }
    }
}

$rawId = $dados['id_anotacao'] ?? null;
$idAnotacao = is_int($rawId)
    ? $rawId
    : (is_string($rawId) && ctype_digit(trim($rawId)) ? (int) trim($rawId) : 0);

$rawConfirmado = $dados['confirmado'] ?? null;
$confirmadoValido = ($rawConfirmado === true || $rawConfirmado === 1)
    || (is_string($rawConfirmado) && in_array(strtolower(trim($rawConfirmado)), ['1', 'true'], true));

if ($idAnotacao <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'erro' => 'Identificador de anotação inválido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$confirmadoValido) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'erro' => 'É necessário confirmar explicitamente que deseja revelar este trecho protegido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// A validação de membro ativo do clube da anotação ocorre dentro do serviço (403 JSON se negado)
$service = new LeituraService();
$anotacao = $service->obterAnotacaoParaRevelacao($idAnotacao);

if ($anotacao === null) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'erro' => 'Anotação não encontrada.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rotulosTipo = [
    'grifo' => 'Grifo',
    'duvida' => 'Dúvida',
    'teoria' => 'Teoria',
    'reflexao' => 'Reflexão',
    'comentario' => 'Comentário',
];

http_response_code(200);
echo json_encode([
    'ok' => true,
    'id_anotacao' => (int) $anotacao['id_anotacao'],
    'tipo' => (string) $anotacao['tipo'],
    'tipo_rotulo' => $rotulosTipo[$anotacao['tipo']] ?? (string) $anotacao['tipo'],
    'posicao_pct' => (float) $anotacao['posicao_pct'],
    'posicao_fmt' => formatarPercentual((float) $anotacao['posicao_pct']),
    'titulo' => $anotacao['titulo'] !== null ? (string) $anotacao['titulo'] : '',
    'conteudo' => (string) $anotacao['conteudo'],
    'autor_apelido' => (string) $anotacao['autor_apelido'],
    'etapa_titulo' => $anotacao['etapa_titulo'] !== null ? (string) $anotacao['etapa_titulo'] : '',
    'criado_em_fmt' => formatarDataHora((string) $anotacao['criado_em']),
], JSON_UNESCAPED_UNICODE);
