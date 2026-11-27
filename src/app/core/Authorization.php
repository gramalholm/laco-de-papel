<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\ConnectionFactory;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Consulta sempre membros_clube no banco em vez de confiar em dados da requisição.
final class Authorization
{
    public static function obterVinculo(int $idClube, int $idUsuario): ?array
    {
        if ($idClube <= 0 || $idUsuario <= 0) {
            return null;
        }

        $pdo = ConnectionFactory::getConnection();
        $stmt = $pdo->prepare(
            "SELECT mc.id_membro_clube, mc.id_clube, mc.id_usuario, mc.papel, mc.status,
                    mc.convidado_por, mc.convidado_em, mc.entrou_em
             FROM membros_clube mc
             JOIN usuarios u ON u.id_usuario = mc.id_usuario
             WHERE mc.id_clube = :id_clube
               AND mc.id_usuario = :id_usuario
               AND u.status = 'ativo'
             LIMIT 1"
        );
        $stmt->execute([
            ':id_clube' => $idClube,
            ':id_usuario' => $idUsuario,
        ]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function exigirMembroAtivo(int $idClube, bool $jsonResponse = false): array
    {
        $usuario = Auth::exigirUsuario($jsonResponse);
        $vinculo = self::obterVinculo($idClube, $usuario['id_usuario']);

        if ($vinculo === null || $vinculo['status'] !== 'ativo') {
            self::negar(
                403,
                'Acesso restrito ao clube',
                'Você precisa participar ativamente deste clube para acessar este conteúdo.',
                $jsonResponse
            );
        }

        return $vinculo;
    }

    public static function exigirResponsavel(int $idClube, bool $jsonResponse = false): array
    {
        $vinculo = self::exigirMembroAtivo($idClube, $jsonResponse);

        if ($vinculo['papel'] !== 'responsavel') {
            self::negar(
                403,
                'Permissão insuficiente',
                'Apenas o responsável deste clube pode executar esta ação.',
                $jsonResponse
            );
        }

        return $vinculo;
    }

    // Define o nível de acesso ao clube: responsavel, membro, convidado, visitante_publico ou negado.
    // Clube privado sem vínculo nem convite retorna 'negado' para responder 404 sem confirmar que existe.
    public static function resolverAcessoClube(array $clube, ?int $idUsuario): array
    {
        $idClube = (int) ($clube['id_clube'] ?? 0);
        $vinculo = ($idUsuario !== null && $idUsuario > 0)
            ? self::obterVinculo($idClube, $idUsuario)
            : null;

        if ($vinculo !== null && $vinculo['status'] === 'ativo') {
            return [
                'permitido' => true,
                'nivel' => $vinculo['papel'] === 'responsavel' ? 'responsavel' : 'membro',
                'vinculo' => $vinculo,
            ];
        }

        if ($vinculo !== null && $vinculo['status'] === 'pendente') {
            return [
                'permitido' => true,
                'nivel' => 'convidado',
                'vinculo' => $vinculo,
            ];
        }

        if (($clube['visibilidade'] ?? '') === 'publico') {
            return [
                'permitido' => true,
                'nivel' => 'visitante_publico',
                'vinculo' => $vinculo,
            ];
        }

        return [
            'permitido' => false,
            'nivel' => 'negado',
            'vinculo' => null,
        ];
    }

    private static function negar(int $status, string $titulo, string $mensagem, bool $jsonResponse): never
    {
        if ($jsonResponse) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'erro' => $mensagem,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        View::renderError($status, $titulo, $mensagem);
    }
}
