<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class ProgressoRepository
{
    // Calcula o percentual inline via CASE para não depender de VIEW no banco.
    private const SQL_PCT = "CASE
        WHEN p.formato = 'kindle' THEN ROUND(COALESCE(p.percentual_kindle, 0), 2)
        WHEN p.formato = 'fisico' AND e.total_paginas > 0
             THEN ROUND(LEAST(100, GREATEST(0, (COALESCE(p.pagina_atual, 0) / e.total_paginas) * 100)), 2)
        ELSE 0.00
    END";

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function buscarDoUsuarioNoCiclo(int $idCiclo, int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id_progresso, p.id_ciclo, p.id_clube, p.id_usuario,
                    p.formato, p.id_edicao, p.pagina_atual, p.percentual_kindle,
                    p.status_leitura, p.atualizado_em,
                    e.titulo_edicao, e.editora, e.total_paginas,
                    ' . self::SQL_PCT . ' AS progresso_pct
             FROM progresso_leitores p
             LEFT JOIN edicoes_livro e ON e.id_edicao = p.id_edicao
             WHERE p.id_ciclo = :id_ciclo AND p.id_usuario = :id_usuario
             LIMIT 1'
        );
        $stmt->execute([
            ':id_ciclo' => $idCiclo,
            ':id_usuario' => $idUsuario,
        ]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function obterPercentualUsuario(int $idCiclo, int $idUsuario): float
    {
        $row = $this->buscarDoUsuarioNoCiclo($idCiclo, $idUsuario);
        return $row !== null ? (float) $row['progresso_pct'] : 0.0;
    }

    // Agrupa o avanço do clube em faixas, sem montar ranking individual.
    public function resumoColetivoDoCiclo(int $idCiclo): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::SQL_PCT . ' AS progresso_pct, p.status_leitura
             FROM progresso_leitores p
             LEFT JOIN edicoes_livro e ON e.id_edicao = p.id_edicao
             WHERE p.id_ciclo = :id_ciclo'
        );
        $stmt->execute([':id_ciclo' => $idCiclo]);
        $rows = $stmt->fetchAll();

        $total = count($rows);
        $soma = 0.0;
        $faixas = [
            '0-25' => 0,
            '26-50' => 0,
            '51-75' => 0,
            '76-100' => 0,
        ];

        foreach ($rows as $r) {
            $pct = (float) $r['progresso_pct'];
            $soma += $pct;
            if ($pct <= 25.0) {
                $faixas['0-25']++;
            } elseif ($pct <= 50.0) {
                $faixas['26-50']++;
            } elseif ($pct <= 75.0) {
                $faixas['51-75']++;
            } else {
                $faixas['76-100']++;
            }
        }

        return [
            'total_leitores' => $total,
            'media_pct' => $total > 0 ? round($soma / $total, 1) : 0.0,
            'faixas' => $faixas,
        ];
    }

    public function listarEstanteDoUsuario(int $idUsuario, ?string $statusFiltro = null): array
    {
        $whereExtra = '';
        $params = [':id_usuario' => $idUsuario];

        if ($statusFiltro !== null && in_array($statusFiltro, ['lendo', 'pausado', 'concluido'], true)) {
            $whereExtra = ' AND p.status_leitura = :status_leitura';
            $params[':status_leitura'] = $statusFiltro;
        }

        $sql = 'SELECT p.id_progresso, p.id_ciclo, p.id_clube, p.formato,
                       p.id_edicao, p.pagina_atual, p.percentual_kindle,
                       p.status_leitura, p.atualizado_em,
                       ' . self::SQL_PCT . ' AS progresso_pct,
                       e.titulo_edicao, e.editora, e.total_paginas,
                       cl.titulo_ciclo, cl.status AS status_ciclo,
                       c.nome AS clube_nome, c.cor_identidade AS clube_cor,
                       l.id_livro, l.titulo AS livro_titulo, l.autor_principal AS livro_autor,
                       l.ano_primeira_publicacao AS livro_ano,
                       COALESCE(l.capa_url, e.capa_url) AS livro_capa
                FROM progresso_leitores p
                JOIN ciclos_leitura cl ON cl.id_ciclo = p.id_ciclo
                JOIN clubes c ON c.id_clube = p.id_clube
                JOIN livros l ON l.id_livro = cl.id_livro_escolhido
                LEFT JOIN edicoes_livro e ON e.id_edicao = p.id_edicao
                WHERE p.id_usuario = :id_usuario' . $whereExtra . '
                ORDER BY p.atualizado_em DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Upsert atômico sem usar VALUES() (compatível com MySQL 9 e MariaDB).
    public function salvar(int $idCiclo, int $idClube, int $idUsuario, array $dados): void
    {
        $formato = (string) $dados['formato'];
        $idEdicao = $dados['id_edicao'] !== null ? (int) $dados['id_edicao'] : null;
        $paginaAtual = $dados['pagina_atual'] !== null ? (int) $dados['pagina_atual'] : null;
        $pctKindle = $dados['percentual_kindle'] !== null ? (float) $dados['percentual_kindle'] : null;
        $statusLeitura = (string) $dados['status_leitura'];

        $stmt = $this->pdo->prepare(
            'INSERT INTO progresso_leitores (
                id_ciclo, id_clube, id_usuario, formato, id_edicao,
                pagina_atual, percentual_kindle, status_leitura
             ) VALUES (
                :id_ciclo, :id_clube, :id_usuario, :formato, :id_edicao,
                :pagina_atual, :percentual_kindle, :status_leitura
             )
             ON DUPLICATE KEY UPDATE
                formato = :u_formato,
                id_edicao = :u_id_edicao,
                pagina_atual = :u_pagina_atual,
                percentual_kindle = :u_percentual_kindle,
                status_leitura = :u_status_leitura'
        );
        $stmt->execute([
            ':id_ciclo' => $idCiclo,
            ':id_clube' => $idClube,
            ':id_usuario' => $idUsuario,
            ':formato' => $formato,
            ':id_edicao' => $idEdicao,
            ':pagina_atual' => $paginaAtual,
            ':percentual_kindle' => $pctKindle,
            ':status_leitura' => $statusLeitura,
            ':u_formato' => $formato,
            ':u_id_edicao' => $idEdicao,
            ':u_pagina_atual' => $paginaAtual,
            ':u_percentual_kindle' => $pctKindle,
            ':u_status_leitura' => $statusLeitura,
        ]);
    }
}
