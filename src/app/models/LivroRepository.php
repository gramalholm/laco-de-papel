<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class LivroRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function buscar(?string $termo = null, int $limite = 24): array
    {
        $where = '';
        $params = [];

        $termoLimpo = trim((string) $termo);
        if ($termoLimpo !== '') {
            $where = 'WHERE l.titulo LIKE :termo1 OR l.autor_principal LIKE :termo2';
            $params[':termo1'] = '%' . $termoLimpo . '%';
            $params[':termo2'] = '%' . $termoLimpo . '%';
        }

        $sql = 'SELECT l.id_livro, l.openlibrary_work_id, l.titulo, l.autor_principal,
                       l.descricao, l.ano_primeira_publicacao, l.idioma_original,
                       l.capa_url, l.fonte_metadados, l.criado_por, l.criado_em,
                       COALESCE(l.capa_url, MAX(e.capa_url)) AS capa_exibicao,
                       COUNT(e.id_edicao) AS total_edicoes
                FROM livros l
                LEFT JOIN edicoes_livro e ON e.id_livro = l.id_livro
                ' . $where . '
                GROUP BY l.id_livro, l.openlibrary_work_id, l.titulo, l.autor_principal,
                         l.descricao, l.ano_primeira_publicacao, l.idioma_original,
                         l.capa_url, l.fonte_metadados, l.criado_por, l.criado_em
                ORDER BY l.criado_em DESC, l.id_livro DESC
                LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', max(1, min(60, $limite)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listarRecentes(int $limite = 4): array
    {
        return $this->buscar(null, $limite);
    }

    public function buscarPorId(int $idLivro): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id_livro, l.openlibrary_work_id, l.titulo, l.autor_principal,
                    l.descricao, l.ano_primeira_publicacao, l.idioma_original,
                    l.capa_url, l.fonte_metadados, l.criado_por, l.criado_em, l.atualizado_em,
                    u.apelido AS criado_por_apelido
             FROM livros l
             LEFT JOIN usuarios u ON u.id_usuario = l.criado_por
             WHERE l.id_livro = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idLivro]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function listarParaSelect(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id_livro, titulo, autor_principal, ano_primeira_publicacao
             FROM livros
             ORDER BY titulo ASC, autor_principal ASC'
        );

        return $stmt->fetchAll();
    }

    public function listarPorCriador(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id_livro, l.titulo, l.autor_principal, l.ano_primeira_publicacao,
                    l.criado_em, COUNT(e.id_edicao) AS total_edicoes
             FROM livros l
             LEFT JOIN edicoes_livro e ON e.id_livro = l.id_livro
             WHERE l.criado_por = :id_usuario
             GROUP BY l.id_livro, l.titulo, l.autor_principal, l.ano_primeira_publicacao, l.criado_em
             ORDER BY l.criado_em DESC'
        );
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    // nullIfEmpty() evita colisão de string vazia no índice UNIQUE de openlibrary_work_id.
    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO livros (
                openlibrary_work_id, titulo, autor_principal, descricao,
                ano_primeira_publicacao, idioma_original, capa_url,
                fonte_metadados, criado_por
             ) VALUES (
                :ol_work, :titulo, :autor, :descricao,
                :ano, :idioma, :capa_url,
                :fonte, :criado_por
             )'
        );
        $stmt->execute([
            ':ol_work' => nullIfEmpty(isset($dados['openlibrary_work_id']) ? (string) $dados['openlibrary_work_id'] : null),
            ':titulo' => trim((string) $dados['titulo']),
            ':autor' => trim((string) $dados['autor_principal']),
            ':descricao' => nullIfEmpty(isset($dados['descricao']) ? (string) $dados['descricao'] : null),
            ':ano' => isset($dados['ano_primeira_publicacao']) && trim((string) $dados['ano_primeira_publicacao']) !== '' && (int) $dados['ano_primeira_publicacao'] > 0 ? (int) $dados['ano_primeira_publicacao'] : null,
            ':idioma' => nullIfEmpty(isset($dados['idioma_original']) ? (string) $dados['idioma_original'] : null),
            ':capa_url' => nullIfEmpty(isset($dados['capa_url']) ? (string) $dados['capa_url'] : null),
            ':fonte' => (string) ($dados['fonte_metadados'] ?? 'manual'),
            ':criado_por' => isset($dados['criado_por']) && (int) $dados['criado_por'] > 0 ? (int) $dados['criado_por'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $idLivro, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE livros
             SET titulo = :titulo,
                 autor_principal = :autor,
                 descricao = :descricao,
                 ano_primeira_publicacao = :ano,
                 idioma_original = :idioma,
                 capa_url = :capa_url
             WHERE id_livro = :id_livro'
        );
        $stmt->execute([
            ':titulo' => trim((string) $dados['titulo']),
            ':autor' => trim((string) $dados['autor_principal']),
            ':descricao' => nullIfEmpty(isset($dados['descricao']) ? (string) $dados['descricao'] : null),
            ':ano' => isset($dados['ano_primeira_publicacao']) && trim((string) $dados['ano_primeira_publicacao']) !== '' && (int) $dados['ano_primeira_publicacao'] > 0 ? (int) $dados['ano_primeira_publicacao'] : null,
            ':idioma' => nullIfEmpty(isset($dados['idioma_original']) ? (string) $dados['idioma_original'] : null),
            ':capa_url' => nullIfEmpty(isset($dados['capa_url']) ? (string) $dados['capa_url'] : null),
            ':id_livro' => $idLivro,
        ]);
    }

    public function contarDependencias(int $idLivro): array
    {
        $stmtCiclos = $this->pdo->prepare('SELECT COUNT(*) FROM ciclos_leitura WHERE id_livro_escolhido = :id');
        $stmtCiclos->execute([':id' => $idLivro]);

        $stmtInd = $this->pdo->prepare('SELECT COUNT(*) FROM indicacoes WHERE id_livro = :id');
        $stmtInd->execute([':id' => $idLivro]);

        $stmtEd = $this->pdo->prepare('SELECT COUNT(*) FROM edicoes_livro WHERE id_livro = :id');
        $stmtEd->execute([':id' => $idLivro]);

        return [
            'ciclos' => (int) $stmtCiclos->fetchColumn(),
            'indicacoes' => (int) $stmtInd->fetchColumn(),
            'edicoes' => (int) $stmtEd->fetchColumn(),
        ];
    }

    public function excluir(int $idLivro): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM livros WHERE id_livro = :id');
        $stmt->execute([':id' => $idLivro]);
    }
}
