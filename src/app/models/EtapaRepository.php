<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;
use Throwable;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class EtapaRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function listarPorCiclo(int $idCiclo): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_etapa, id_ciclo, id_clube, ordem, titulo, pergunta,
                    percentual_alvo, data_limite, criado_em
             FROM etapas_leitura
             WHERE id_ciclo = :id_ciclo
             ORDER BY ordem ASC, percentual_alvo ASC'
        );
        $stmt->execute([':id_ciclo' => $idCiclo]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idEtapa): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, cl.titulo_ciclo, cl.status AS ciclo_status,
                    c.nome AS clube_nome, c.visibilidade AS clube_visibilidade, c.status AS clube_status
             FROM etapas_leitura e
             JOIN ciclos_leitura cl ON cl.id_ciclo = e.id_ciclo
             JOIN clubes c ON c.id_clube = e.id_clube
             WHERE e.id_etapa = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idEtapa]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function proximaOrdem(int $idCiclo): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(ordem), 0) + 1 FROM etapas_leitura WHERE id_ciclo = :id_ciclo'
        );
        $stmt->execute([':id_ciclo' => $idCiclo]);

        return (int) $stmt->fetchColumn();
    }

    public function existeOrdemNoCiclo(int $idCiclo, int $ordem, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null && $ignorarId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM etapas_leitura WHERE id_ciclo = :id_ciclo AND ordem = :ordem AND id_etapa <> :id LIMIT 1'
            );
            $stmt->execute([':id_ciclo' => $idCiclo, ':ordem' => $ordem, ':id' => $ignorarId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM etapas_leitura WHERE id_ciclo = :id_ciclo AND ordem = :ordem LIMIT 1'
            );
            $stmt->execute([':id_ciclo' => $idCiclo, ':ordem' => $ordem]);
        }

        return $stmt->fetchColumn() !== false;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO etapas_leitura (
                id_ciclo, id_clube, ordem, titulo, pergunta, percentual_alvo, data_limite
             ) VALUES (
                :id_ciclo, :id_clube, :ordem, :titulo, :pergunta, :percentual_alvo, :data_limite
             )'
        );
        $stmt->execute([
            ':id_ciclo' => (int) $dados['id_ciclo'],
            ':id_clube' => (int) $dados['id_clube'],
            ':ordem' => (int) $dados['ordem'],
            ':titulo' => trim((string) $dados['titulo']),
            ':pergunta' => nullIfEmpty(isset($dados['pergunta']) ? (string) $dados['pergunta'] : null),
            ':percentual_alvo' => (float) $dados['percentual_alvo'],
            ':data_limite' => nullIfEmpty(isset($dados['data_limite']) ? (string) $dados['data_limite'] : null),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $idEtapa, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE etapas_leitura
             SET ordem = :ordem,
                 titulo = :titulo,
                 pergunta = :pergunta,
                 percentual_alvo = :percentual_alvo,
                 data_limite = :data_limite
             WHERE id_etapa = :id_etapa'
        );
        $stmt->execute([
            ':ordem' => (int) $dados['ordem'],
            ':titulo' => trim((string) $dados['titulo']),
            ':pergunta' => nullIfEmpty(isset($dados['pergunta']) ? (string) $dados['pergunta'] : null),
            ':percentual_alvo' => (float) $dados['percentual_alvo'],
            ':data_limite' => nullIfEmpty(isset($dados['data_limite']) ? (string) $dados['data_limite'] : null),
            ':id_etapa' => $idEtapa,
        ]);
    }

    public function excluir(int $idEtapa): void
    {
        // Zera id_etapa antes do DELETE na mesma transação: preserva as anotações e evita erro de FK.
        $abriuTransacao = !$this->pdo->inTransaction();
        if ($abriuTransacao) {
            $this->pdo->beginTransaction();
        }

        try {
            $lock = $this->pdo->prepare('SELECT id_etapa FROM etapas_leitura WHERE id_etapa = :id FOR UPDATE');
            $lock->execute([':id' => $idEtapa]);

            $stmtUnlink = $this->pdo->prepare('UPDATE anotacoes SET id_etapa = NULL WHERE id_etapa = :id');
            $stmtUnlink->execute([':id' => $idEtapa]);

            $stmt = $this->pdo->prepare('DELETE FROM etapas_leitura WHERE id_etapa = :id');
            $stmt->execute([':id' => $idEtapa]);

            if ($abriuTransacao) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($abriuTransacao && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
