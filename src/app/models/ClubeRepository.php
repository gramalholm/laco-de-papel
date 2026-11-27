<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class ClubeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    // Clubes privados e arquivados nunca entram em buscas públicas; o filtro fica travado no SQL.
    public function buscarPublicos(?string $termo = null, ?int $idTema = null, int $limite = 20): array
    {
        $condicoes = [
            "c.visibilidade = 'publico'",
            "c.status = 'ativo'",
        ];
        $params = [];

        $termoLimpo = trim((string) $termo);
        if ($termoLimpo !== '') {
            $condicoes[] = 'c.nome LIKE :termo';
            $params[':termo'] = '%' . $termoLimpo . '%';
        }

        if ($idTema !== null && $idTema > 0) {
            $condicoes[] = 'c.id_tema = :id_tema';
            $params[':id_tema'] = $idTema;
        }

        $sql = 'SELECT c.id_clube, c.id_tema, c.nome, c.slug, c.descricao,
                       c.visibilidade, c.cor_identidade, c.status, c.criado_em,
                       t.nome AS tema_nome, t.slug AS tema_slug,
                       COUNT(DISTINCT mc.id_usuario) AS total_membros,
                       cl.id_ciclo AS ciclo_ativo_id,
                       cl.status AS ciclo_ativo_status,
                       cl.titulo_ciclo AS ciclo_ativo_titulo,
                       l.titulo AS ciclo_ativo_livro,
                       l.autor_principal AS ciclo_ativo_autor
                FROM clubes c
                JOIN temas_clube t ON t.id_tema = c.id_tema
                LEFT JOIN membros_clube mc
                    ON mc.id_clube = c.id_clube
                   AND mc.status = \'ativo\'
                LEFT JOIN ciclos_leitura cl
                    ON cl.id_clube = c.id_clube
                   AND cl.status IN (\'votacao\', \'em_andamento\')
                LEFT JOIN livros l
                    ON l.id_livro = cl.id_livro_escolhido
                WHERE ' . implode(' AND ', $condicoes) . '
                GROUP BY c.id_clube, c.id_tema, c.nome, c.slug, c.descricao,
                         c.visibilidade, c.cor_identidade, c.status, c.criado_em,
                         t.nome, t.slug,
                         cl.id_ciclo, cl.status, cl.titulo_ciclo, l.titulo, l.autor_principal
                ORDER BY c.criado_em DESC
                LIMIT :limite';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', max(1, min(50, $limite)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarDestaquesPublicos(int $limite = 4): array
    {
        return $this->buscarPublicos(null, null, $limite);
    }

    // Não restringe visibilidade; quem chama precisa checar permissão em clubes privados.
    public function buscarPorId(int $idClube): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id_clube, c.id_tema, c.id_criador, c.nome, c.slug,
                    c.descricao, c.regras, c.visibilidade, c.cor_identidade,
                    c.status, c.criado_em, c.atualizado_em,
                    t.nome AS tema_nome, t.slug AS tema_slug,
                    u.apelido AS criador_apelido
             FROM clubes c
             JOIN temas_clube t ON t.id_tema = c.id_tema
             JOIN usuarios u ON u.id_usuario = c.id_criador
             WHERE c.id_clube = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idClube]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function existeSlug(string $slug, ?int $ignorarIdClube = null): bool
    {
        if ($ignorarIdClube !== null && $ignorarIdClube > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM clubes WHERE slug = :slug AND id_clube <> :id LIMIT 1'
            );
            $stmt->execute([':slug' => $slug, ':id' => $ignorarIdClube]);
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM clubes WHERE slug = :slug LIMIT 1');
            $stmt->execute([':slug' => $slug]);
        }

        return $stmt->fetchColumn() !== false;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clubes (
                id_tema, id_criador, nome, slug, descricao, regras,
                visibilidade, cor_identidade, status
             ) VALUES (
                :id_tema, :id_criador, :nome, :slug, :descricao, :regras,
                :visibilidade, :cor_identidade, :status
             )'
        );
        $stmt->execute([
            ':id_tema' => (int) $dados['id_tema'],
            ':id_criador' => (int) $dados['id_criador'],
            ':nome' => trim((string) $dados['nome']),
            ':slug' => (string) $dados['slug'],
            ':descricao' => trim((string) $dados['descricao']),
            ':regras' => nullIfEmpty(isset($dados['regras']) ? (string) $dados['regras'] : null),
            ':visibilidade' => (string) $dados['visibilidade'],
            ':cor_identidade' => (string) $dados['cor_identidade'],
            ':status' => 'ativo',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $idClube, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE clubes
             SET id_tema = :id_tema,
                 nome = :nome,
                 descricao = :descricao,
                 regras = :regras,
                 visibilidade = :visibilidade,
                 cor_identidade = :cor_identidade
             WHERE id_clube = :id_clube'
        );
        $stmt->execute([
            ':id_tema' => (int) $dados['id_tema'],
            ':nome' => trim((string) $dados['nome']),
            ':descricao' => trim((string) $dados['descricao']),
            ':regras' => nullIfEmpty(isset($dados['regras']) ? (string) $dados['regras'] : null),
            ':visibilidade' => (string) $dados['visibilidade'],
            ':cor_identidade' => (string) $dados['cor_identidade'],
            ':id_clube' => $idClube,
        ]);
    }

    public function arquivar(int $idClube): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE clubes SET status = 'arquivado' WHERE id_clube = :id_clube"
        );
        $stmt->execute([':id_clube' => $idClube]);
    }
}
