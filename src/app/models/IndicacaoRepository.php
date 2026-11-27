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

final class IndicacaoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    // Em caso de empate nos votos, desempata por ordem de criação.
    public function listarComVotos(int $idCiclo): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.id_indicacao, i.id_ciclo, i.id_clube, i.id_livro, i.id_usuario,
                    i.justificativa, i.criado_em,
                    l.titulo AS livro_titulo, l.autor_principal AS livro_autor,
                    l.ano_primeira_publicacao AS livro_ano,
                    COALESCE(l.capa_url, (SELECT MAX(e.capa_url) FROM edicoes_livro e WHERE e.id_livro = i.id_livro)) AS livro_capa,
                    u.apelido AS indicado_por_apelido,
                    COUNT(v.id_voto) AS total_votos
             FROM indicacoes i
             JOIN livros l ON l.id_livro = i.id_livro
             JOIN usuarios u ON u.id_usuario = i.id_usuario
             LEFT JOIN votos v ON v.id_indicacao = i.id_indicacao
             WHERE i.id_ciclo = :id_ciclo
             GROUP BY i.id_indicacao, i.id_ciclo, i.id_clube, i.id_livro, i.id_usuario,
                      i.justificativa, i.criado_em,
                      l.titulo, l.autor_principal, l.ano_primeira_publicacao, l.capa_url,
                      u.apelido
             ORDER BY total_votos DESC, i.criado_em ASC, i.id_indicacao ASC'
        );
        $stmt->execute([':id_ciclo' => $idCiclo]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idIndicacao): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, l.titulo AS livro_titulo,
                    cl.status AS ciclo_status,
                    c.visibilidade AS clube_visibilidade, c.status AS clube_status
             FROM indicacoes i
             JOIN livros l ON l.id_livro = i.id_livro
             JOIN ciclos_leitura cl ON cl.id_ciclo = i.id_ciclo
             JOIN clubes c ON c.id_clube = i.id_clube
             WHERE i.id_indicacao = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idIndicacao]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function existeLivroNoCiclo(int $idCiclo, int $idLivro): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM indicacoes WHERE id_ciclo = :id_ciclo AND id_livro = :id_livro LIMIT 1'
        );
        $stmt->execute([':id_ciclo' => $idCiclo, ':id_livro' => $idLivro]);

        return $stmt->fetchColumn() !== false;
    }

    public function criar(int $idCiclo, int $idClube, int $idLivro, int $idUsuario, ?string $justificativa): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO indicacoes (id_ciclo, id_clube, id_livro, id_usuario, justificativa)
             VALUES (:id_ciclo, :id_clube, :id_livro, :id_usuario, :justificativa)'
        );
        $stmt->execute([
            ':id_ciclo' => $idCiclo,
            ':id_clube' => $idClube,
            ':id_livro' => $idLivro,
            ':id_usuario' => $idUsuario,
            ':justificativa' => nullIfEmpty($justificativa),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function excluir(int $idIndicacao): void
    {
        // Apaga os votos antes na mesma transação porque a FK composta não usa ON DELETE CASCADE.
        $abriuTransacao = !$this->pdo->inTransaction();
        if ($abriuTransacao) {
            $this->pdo->beginTransaction();
        }

        try {
            $lock = $this->pdo->prepare('SELECT id_indicacao FROM indicacoes WHERE id_indicacao = :id FOR UPDATE');
            $lock->execute([':id' => $idIndicacao]);

            $stmtVotos = $this->pdo->prepare('DELETE FROM votos WHERE id_indicacao = :id');
            $stmtVotos->execute([':id' => $idIndicacao]);

            $stmt = $this->pdo->prepare('DELETE FROM indicacoes WHERE id_indicacao = :id');
            $stmt->execute([':id' => $idIndicacao]);

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
