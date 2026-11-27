<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class EdicaoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function listarPorLivro(int $idLivro): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.id_edicao, e.id_livro, e.isbn10, e.isbn13, e.titulo_edicao,
                    e.editora, e.ano_publicacao, e.idioma, e.total_paginas,
                    e.capa_url, e.fonte_metadados, e.criado_por, e.criado_em,
                    u.apelido AS criado_por_apelido
             FROM edicoes_livro e
             LEFT JOIN usuarios u ON u.id_usuario = e.criado_por
             WHERE e.id_livro = :id_livro
             ORDER BY (e.total_paginas IS NULL) ASC, e.ano_publicacao DESC, e.id_edicao DESC'
        );
        $stmt->execute([':id_livro' => $idLivro]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idEdicao): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, l.titulo AS livro_titulo, l.autor_principal AS livro_autor
             FROM edicoes_livro e
             JOIN livros l ON l.id_livro = e.id_livro
             WHERE e.id_edicao = :id_edicao
             LIMIT 1'
        );
        $stmt->execute([':id_edicao' => $idEdicao]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function listarPorCriador(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.id_edicao, e.id_livro, e.titulo_edicao, e.editora,
                    e.ano_publicacao, e.total_paginas, e.isbn13,
                    l.titulo AS livro_titulo
             FROM edicoes_livro e
             JOIN livros l ON l.id_livro = e.id_livro
             WHERE e.criado_por = :id_usuario
             ORDER BY e.criado_em DESC'
        );
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    public function existeIsbn10(string $isbn10, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null && $ignorarId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM edicoes_livro WHERE isbn10 = :isbn AND id_edicao <> :id LIMIT 1'
            );
            $stmt->execute([':isbn' => $isbn10, ':id' => $ignorarId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM edicoes_livro WHERE isbn10 = :isbn LIMIT 1');
            $stmt->execute([':isbn' => $isbn10]);
        }

        return $stmt->fetchColumn() !== false;
    }

    public function existeIsbn13(string $isbn13, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null && $ignorarId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM edicoes_livro WHERE isbn13 = :isbn AND id_edicao <> :id LIMIT 1'
            );
            $stmt->execute([':isbn' => $isbn13, ':id' => $ignorarId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM edicoes_livro WHERE isbn13 = :isbn LIMIT 1');
            $stmt->execute([':isbn' => $isbn13]);
        }

        return $stmt->fetchColumn() !== false;
    }

    // Campos opcionais com índice UNIQUE (isbn10, isbn13, openlibrary_edition_id) usam nullIfEmpty():
    // o banco aceita múltiplos NULL, mas colidiria com string vazia.
    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO edicoes_livro (
                id_livro, openlibrary_edition_id, isbn10, isbn13,
                titulo_edicao, editora, ano_publicacao, idioma,
                total_paginas, capa_url, fonte_metadados, criado_por
             ) VALUES (
                :id_livro, :ol_ed, :isbn10, :isbn13,
                :titulo_edicao, :editora, :ano, :idioma,
                :paginas, :capa_url, :fonte, :criado_por
             )'
        );
        $stmt->execute([
            ':id_livro' => (int) $dados['id_livro'],
            ':ol_ed' => nullIfEmpty(isset($dados['openlibrary_edition_id']) ? (string) $dados['openlibrary_edition_id'] : null),
            ':isbn10' => nullIfEmpty(isset($dados['isbn10']) ? (string) $dados['isbn10'] : null),
            ':isbn13' => nullIfEmpty(isset($dados['isbn13']) ? (string) $dados['isbn13'] : null),
            ':titulo_edicao' => nullIfEmpty(isset($dados['titulo_edicao']) ? (string) $dados['titulo_edicao'] : null),
            ':editora' => nullIfEmpty(isset($dados['editora']) ? (string) $dados['editora'] : null),
            ':ano' => isset($dados['ano_publicacao']) && trim((string) $dados['ano_publicacao']) !== '' && (int) $dados['ano_publicacao'] > 0 ? (int) $dados['ano_publicacao'] : null,
            ':idioma' => nullIfEmpty(isset($dados['idioma']) ? (string) $dados['idioma'] : null),
            ':paginas' => isset($dados['total_paginas']) && trim((string) $dados['total_paginas']) !== '' && (int) $dados['total_paginas'] > 0 ? (int) $dados['total_paginas'] : null,
            ':capa_url' => nullIfEmpty(isset($dados['capa_url']) ? (string) $dados['capa_url'] : null),
            ':fonte' => (string) ($dados['fonte_metadados'] ?? 'manual'),
            ':criado_por' => isset($dados['criado_por']) && (int) $dados['criado_por'] > 0 ? (int) $dados['criado_por'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $idEdicao, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE edicoes_livro
             SET isbn10 = :isbn10,
                 isbn13 = :isbn13,
                 titulo_edicao = :titulo_edicao,
                 editora = :editora,
                 ano_publicacao = :ano,
                 idioma = :idioma,
                 total_paginas = :paginas,
                 capa_url = :capa_url
             WHERE id_edicao = :id_edicao'
        );
        $stmt->execute([
            ':isbn10' => nullIfEmpty(isset($dados['isbn10']) ? (string) $dados['isbn10'] : null),
            ':isbn13' => nullIfEmpty(isset($dados['isbn13']) ? (string) $dados['isbn13'] : null),
            ':titulo_edicao' => nullIfEmpty(isset($dados['titulo_edicao']) ? (string) $dados['titulo_edicao'] : null),
            ':editora' => nullIfEmpty(isset($dados['editora']) ? (string) $dados['editora'] : null),
            ':ano' => isset($dados['ano_publicacao']) && trim((string) $dados['ano_publicacao']) !== '' && (int) $dados['ano_publicacao'] > 0 ? (int) $dados['ano_publicacao'] : null,
            ':idioma' => nullIfEmpty(isset($dados['idioma']) ? (string) $dados['idioma'] : null),
            ':paginas' => isset($dados['total_paginas']) && trim((string) $dados['total_paginas']) !== '' && (int) $dados['total_paginas'] > 0 ? (int) $dados['total_paginas'] : null,
            ':capa_url' => nullIfEmpty(isset($dados['capa_url']) ? (string) $dados['capa_url'] : null),
            ':id_edicao' => $idEdicao,
        ]);
    }

    public function contarDependencias(int $idEdicao): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM progresso_leitores WHERE id_edicao = :id');
        $stmt->execute([':id' => $idEdicao]);

        return (int) $stmt->fetchColumn();
    }

    public function excluir(int $idEdicao): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM edicoes_livro WHERE id_edicao = :id');
        $stmt->execute([':id' => $idEdicao]);
    }
}
