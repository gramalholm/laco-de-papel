<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class AnotacaoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    // Anti-spoiler aplicado no próprio SQL: anotações adiante do progresso do leitor (exceto as próprias)
    // vêm com titulo e conteudo NULL para o texto não sair do banco antes da revelação.
    public function listarDoCicloParaLeitor(
        int $idCiclo,
        int $idUsuario,
        float $progressoLeitor,
        ?string $tipo = null,
        ?int $idEtapa = null
    ): array {
        $condicoes = ['a.id_ciclo = :id_ciclo'];
        $params = [
            ':prog1' => $progressoLeitor,
            ':uid1' => $idUsuario,
            ':prog2' => $progressoLeitor,
            ':uid2' => $idUsuario,
            ':prog3' => $progressoLeitor,
            ':uid3' => $idUsuario,
            ':id_ciclo' => $idCiclo,
        ];

        $tiposValidos = ['grifo', 'duvida', 'teoria', 'reflexao', 'comentario'];
        if ($tipo !== null && in_array($tipo, $tiposValidos, true)) {
            $condicoes[] = 'a.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if ($idEtapa !== null && $idEtapa > 0) {
            $condicoes[] = 'a.id_etapa = :id_etapa';
            $params[':id_etapa'] = $idEtapa;
        }

        $sql = 'SELECT a.id_anotacao, a.id_ciclo, a.id_clube, a.id_usuario, a.id_etapa,
                       a.tipo, a.posicao_pct, a.criado_em, a.atualizado_em,
                       u.nome AS autor_nome, u.apelido AS autor_apelido,
                       et.ordem AS etapa_ordem, et.titulo AS etapa_titulo,
                       CASE
                           WHEN a.posicao_pct <= :prog1 OR a.id_usuario = :uid1 THEN a.titulo
                           ELSE NULL
                       END AS titulo,
                       CASE
                           WHEN a.posicao_pct <= :prog2 OR a.id_usuario = :uid2 THEN a.conteudo
                           ELSE NULL
                       END AS conteudo,
                       CASE
                           WHEN a.posicao_pct > :prog3 AND a.id_usuario <> :uid3 THEN 1
                           ELSE 0
                       END AS eh_spoiler
                FROM anotacoes a
                JOIN usuarios u ON u.id_usuario = a.id_usuario
                LEFT JOIN etapas_leitura et ON et.id_etapa = a.id_etapa
                WHERE ' . implode(' AND ', $condicoes) . '
                ORDER BY a.posicao_pct ASC, a.criado_em DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Traz o texto completo sem mascarar; usar só na revelação autorizada ou na edição/exclusão.
    public function buscarPorId(int $idAnotacao): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.nome AS autor_nome, u.apelido AS autor_apelido,
                    et.ordem AS etapa_ordem, et.titulo AS etapa_titulo,
                    c.nome AS clube_nome, c.visibilidade AS clube_visibilidade,
                    c.status AS clube_status, cl.status AS ciclo_status,
                    l.titulo AS livro_titulo
             FROM anotacoes a
             JOIN usuarios u ON u.id_usuario = a.id_usuario
             JOIN clubes c ON c.id_clube = a.id_clube
             JOIN ciclos_leitura cl ON cl.id_ciclo = a.id_ciclo
             LEFT JOIN livros l ON l.id_livro = cl.id_livro_escolhido
             LEFT JOIN etapas_leitura et ON et.id_etapa = a.id_etapa
             WHERE a.id_anotacao = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idAnotacao]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function listarPorAutor(int $idUsuario, bool $apenasEditaveis = false): array
    {
        $filtroExtra = $apenasEditaveis
            ? " AND cl.status = 'em_andamento' AND c.status = 'ativo' AND mc.status = 'ativo'"
            : '';

        $stmt = $this->pdo->prepare(
            "SELECT a.id_anotacao, a.id_ciclo, a.id_clube, a.tipo, a.titulo,
                    a.posicao_pct, a.criado_em,
                    c.nome AS clube_nome, c.status AS clube_status,
                    cl.status AS ciclo_status,
                    l.titulo AS livro_titulo
             FROM anotacoes a
             JOIN clubes c ON c.id_clube = a.id_clube
             JOIN ciclos_leitura cl ON cl.id_ciclo = a.id_ciclo
             LEFT JOIN membros_clube mc
                 ON mc.id_clube = a.id_clube
                AND mc.id_usuario = a.id_usuario
             LEFT JOIN livros l ON l.id_livro = cl.id_livro_escolhido
             WHERE a.id_usuario = :id_usuario" . $filtroExtra . '
             ORDER BY a.criado_em DESC
             LIMIT 40'
        );
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO anotacoes (
                id_ciclo, id_clube, id_usuario, id_etapa,
                tipo, titulo, conteudo, posicao_pct
             ) VALUES (
                :id_ciclo, :id_clube, :id_usuario, :id_etapa,
                :tipo, :titulo, :conteudo, :posicao_pct
             )'
        );
        $stmt->execute([
            ':id_ciclo' => (int) $dados['id_ciclo'],
            ':id_clube' => (int) $dados['id_clube'],
            ':id_usuario' => (int) $dados['id_usuario'],
            ':id_etapa' => isset($dados['id_etapa']) && (int) $dados['id_etapa'] > 0 ? (int) $dados['id_etapa'] : null,
            ':tipo' => (string) $dados['tipo'],
            ':titulo' => nullIfEmpty(isset($dados['titulo']) ? (string) $dados['titulo'] : null),
            ':conteudo' => trim((string) $dados['conteudo']),
            ':posicao_pct' => (float) $dados['posicao_pct'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $idAnotacao, array $dados): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE anotacoes
             SET id_etapa = :id_etapa,
                 tipo = :tipo,
                 titulo = :titulo,
                 conteudo = :conteudo,
                 posicao_pct = :posicao_pct
             WHERE id_anotacao = :id_anotacao'
        );
        $stmt->execute([
            ':id_etapa' => isset($dados['id_etapa']) && (int) $dados['id_etapa'] > 0 ? (int) $dados['id_etapa'] : null,
            ':tipo' => (string) $dados['tipo'],
            ':titulo' => nullIfEmpty(isset($dados['titulo']) ? (string) $dados['titulo'] : null),
            ':conteudo' => trim((string) $dados['conteudo']),
            ':posicao_pct' => (float) $dados['posicao_pct'],
            ':id_anotacao' => $idAnotacao,
        ]);
    }

    public function excluir(int $idAnotacao): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM anotacoes WHERE id_anotacao = :id');
        $stmt->execute([':id' => $idAnotacao]);
    }
}
