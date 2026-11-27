<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class CicloRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function buscarPorId(int $idCiclo): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cl.id_ciclo, cl.id_clube, cl.id_livro_escolhido, cl.titulo_ciclo,
                    cl.status, cl.data_inicio, cl.data_limite, cl.data_encerramento,
                    cl.encontro_em, cl.encontro_local, cl.encontro_url,
                    cl.criado_por, cl.criado_em, cl.atualizado_em,
                    c.nome AS clube_nome, c.visibilidade AS clube_visibilidade,
                    c.cor_identidade AS clube_cor, c.status AS clube_status,
                    t.nome AS clube_tema_nome,
                    l.titulo AS livro_titulo, l.autor_principal AS livro_autor,
                    l.ano_primeira_publicacao AS livro_ano, l.capa_url AS livro_capa_url
             FROM ciclos_leitura cl
             JOIN clubes c ON c.id_clube = cl.id_clube
             JOIN temas_clube t ON t.id_tema = c.id_tema
             LEFT JOIN livros l ON l.id_livro = cl.id_livro_escolhido
             WHERE cl.id_ciclo = :id_ciclo
             LIMIT 1'
        );
        $stmt->execute([':id_ciclo' => $idCiclo]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    // Cada clube mantém no máximo um ciclo aberto; com $forUpdate consulta só ciclos_leitura sem travar livros.
    public function buscarAtivoDoClube(int $idClube, bool $forUpdate = false): ?array
    {
        if ($forUpdate) {
            $stmt = $this->pdo->prepare(
                "SELECT id_ciclo, id_clube, id_livro_escolhido, status
                 FROM ciclos_leitura
                 WHERE id_clube = :id_clube
                   AND status IN ('votacao', 'em_andamento')
                 ORDER BY criado_em DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':id_clube' => $idClube]);
            $row = $stmt->fetch();

            return is_array($row) ? $row : null;
        }

        $sql = "SELECT cl.id_ciclo, cl.id_clube, cl.id_livro_escolhido, cl.titulo_ciclo,
                       cl.status, cl.data_inicio, cl.data_limite,
                       cl.encontro_em, cl.encontro_local, cl.encontro_url,
                       cl.criado_em,
                       l.titulo AS livro_titulo, l.autor_principal AS livro_autor,
                       l.ano_primeira_publicacao AS livro_ano,
                       COALESCE(l.capa_url, (SELECT MAX(e.capa_url) FROM edicoes_livro e WHERE e.id_livro = l.id_livro)) AS livro_capa
                FROM ciclos_leitura cl
                LEFT JOIN livros l ON l.id_livro = cl.id_livro_escolhido
                WHERE cl.id_clube = :id_clube
                  AND cl.status IN ('votacao', 'em_andamento')
                ORDER BY cl.criado_em DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_clube' => $idClube]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function listarPorClube(int $idClube): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cl.id_ciclo, cl.id_livro_escolhido, cl.titulo_ciclo, cl.status,
                    cl.data_inicio, cl.data_limite, cl.data_encerramento,
                    cl.encontro_em, cl.criado_em,
                    l.titulo AS livro_titulo, l.autor_principal AS livro_autor
             FROM ciclos_leitura cl
             LEFT JOIN livros l ON l.id_livro = cl.id_livro_escolhido
             WHERE cl.id_clube = :id_clube
             ORDER BY cl.criado_em DESC, cl.id_ciclo DESC'
        );
        $stmt->execute([':id_clube' => $idClube]);

        return $stmt->fetchAll();
    }

    // Clubes privados só aparecem se o usuário atual for membro ativo; sem login, entram apenas públicos.
    public function listarCiclosVisiveisDoLivro(int $idLivro, ?int $idUsuario): array
    {
        if ($idUsuario !== null && $idUsuario > 0) {
            $stmt = $this->pdo->prepare(
                "SELECT cl.id_ciclo, cl.titulo_ciclo, cl.status, cl.data_inicio,
                        c.id_clube, c.nome AS clube_nome, c.visibilidade
                 FROM ciclos_leitura cl
                 JOIN clubes c ON c.id_clube = cl.id_clube
                 LEFT JOIN membros_clube mc
                     ON mc.id_clube = c.id_clube
                    AND mc.id_usuario = :id_usuario
                    AND mc.status = 'ativo'
                 WHERE cl.id_livro_escolhido = :id_livro
                   AND c.status = 'ativo'
                   AND (c.visibilidade = 'publico' OR mc.id_usuario IS NOT NULL)
                 ORDER BY cl.criado_em DESC
                 LIMIT 10"
            );
            $stmt->execute([
                ':id_livro' => $idLivro,
                ':id_usuario' => $idUsuario,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT cl.id_ciclo, cl.titulo_ciclo, cl.status, cl.data_inicio,
                        c.id_clube, c.nome AS clube_nome, c.visibilidade
                 FROM ciclos_leitura cl
                 JOIN clubes c ON c.id_clube = cl.id_clube
                 WHERE cl.id_livro_escolhido = :id_livro
                   AND c.status = 'ativo'
                   AND c.visibilidade = 'publico'
                 ORDER BY cl.criado_em DESC
                 LIMIT 10"
            );
            $stmt->execute([':id_livro' => $idLivro]);
        }

        return $stmt->fetchAll();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ciclos_leitura (
                id_clube, id_livro_escolhido, titulo_ciclo, status,
                data_inicio, data_limite, criado_por
             ) VALUES (
                :id_clube, :id_livro, :titulo, :status,
                :data_inicio, :data_limite, :criado_por
             )'
        );
        $stmt->execute([
            ':id_clube' => (int) $dados['id_clube'],
            ':id_livro' => isset($dados['id_livro_escolhido']) && (int) $dados['id_livro_escolhido'] > 0 ? (int) $dados['id_livro_escolhido'] : null,
            ':titulo' => nullIfEmpty(isset($dados['titulo_ciclo']) ? (string) $dados['titulo_ciclo'] : null),
            ':status' => (string) $dados['status'],
            ':data_inicio' => nullIfEmpty(isset($dados['data_inicio']) ? (string) $dados['data_inicio'] : null),
            ':data_limite' => nullIfEmpty(isset($dados['data_limite']) ? (string) $dados['data_limite'] : null),
            ':criado_por' => (int) $dados['criado_por'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function iniciarLeitura(int $idCiclo, int $idLivroEscolhido): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ciclos_leitura
             SET id_livro_escolhido = :id_livro,
                 status = 'em_andamento',
                 data_inicio = COALESCE(data_inicio, CURDATE())
             WHERE id_ciclo = :id_ciclo
               AND status = 'votacao'"
        );
        $stmt->execute([
            ':id_livro' => $idLivroEscolhido,
            ':id_ciclo' => $idCiclo,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function atualizarEncontro(
        int $idCiclo,
        ?string $encontroEm,
        ?string $encontroLocal,
        ?string $encontroUrl
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE ciclos_leitura
             SET encontro_em = :encontro_em,
                 encontro_local = :encontro_local,
                 encontro_url = :encontro_url
             WHERE id_ciclo = :id_ciclo'
        );
        $stmt->execute([
            ':encontro_em' => nullIfEmpty($encontroEm),
            ':encontro_local' => nullIfEmpty($encontroLocal),
            ':encontro_url' => nullIfEmpty($encontroUrl),
            ':id_ciclo' => $idCiclo,
        ]);
    }

    public function encerrar(int $idCiclo): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ciclos_leitura
             SET status = 'encerrado',
                 data_encerramento = NOW()
             WHERE id_ciclo = :id_ciclo"
        );
        $stmt->execute([':id_ciclo' => $idCiclo]);
    }

    public function encerrarAbertosDoClube(int $idClube): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ciclos_leitura
             SET status = 'encerrado',
                 data_encerramento = NOW()
             WHERE id_clube = :id_clube
               AND status IN ('votacao', 'em_andamento')"
        );
        $stmt->execute([':id_clube' => $idClube]);
    }
}
