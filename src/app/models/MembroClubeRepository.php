<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class MembroClubeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function buscarVinculo(int $idClube, int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_membro_clube, id_clube, id_usuario, papel, status,
                    convidado_por, convidado_em, entrou_em
             FROM membros_clube
             WHERE id_clube = :id_clube AND id_usuario = :id_usuario
             LIMIT 1'
        );
        $stmt->execute([
            ':id_clube' => $idClube,
            ':id_usuario' => $idUsuario,
        ]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function listarMembrosAtivos(int $idClube): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT mc.id_membro_clube, mc.id_usuario, mc.papel, mc.entrou_em,
                    u.nome, u.apelido
             FROM membros_clube mc
             JOIN usuarios u ON u.id_usuario = mc.id_usuario
             WHERE mc.id_clube = :id_clube
               AND mc.status = 'ativo'
             ORDER BY (mc.papel = 'responsavel') DESC, u.apelido ASC"
        );
        $stmt->execute([':id_clube' => $idClube]);

        return $stmt->fetchAll();
    }

    public function listarConvitesPendentesDoClube(int $idClube): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT mc.id_membro_clube, mc.id_usuario, mc.convidado_em,
                    u.nome, u.apelido
             FROM membros_clube mc
             JOIN usuarios u ON u.id_usuario = mc.id_usuario
             WHERE mc.id_clube = :id_clube
               AND mc.status = 'pendente'
             ORDER BY mc.convidado_em DESC"
        );
        $stmt->execute([':id_clube' => $idClube]);

        return $stmt->fetchAll();
    }

    public function listarClubesDoUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id_clube, c.nome, c.slug, c.descricao, c.visibilidade,
                    c.cor_identidade, c.status AS status_clube,
                    mc.papel, mc.status AS status_membro, mc.convidado_em, mc.entrou_em,
                    t.nome AS tema_nome,
                    conv.apelido AS convidado_por_apelido,
                    cl.id_ciclo AS ciclo_ativo_id,
                    cl.status AS ciclo_ativo_status,
                    l.titulo AS ciclo_ativo_livro
             FROM membros_clube mc
             JOIN clubes c ON c.id_clube = mc.id_clube
             JOIN temas_clube t ON t.id_tema = c.id_tema
             LEFT JOIN usuarios conv ON conv.id_usuario = mc.convidado_por
             LEFT JOIN ciclos_leitura cl
                 ON cl.id_clube = c.id_clube
                AND cl.status IN ('votacao', 'em_andamento')
             LEFT JOIN livros l ON l.id_livro = cl.id_livro_escolhido
             WHERE mc.id_usuario = :id_usuario
               AND mc.status IN ('ativo', 'pendente')
             ORDER BY (mc.status = 'pendente') DESC, (c.status = 'ativo') DESC, c.nome ASC"
        );
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    public function contarMembrosAtivos(int $idClube): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM membros_clube WHERE id_clube = :id_clube AND status = 'ativo'"
        );
        $stmt->execute([':id_clube' => $idClube]);

        return (int) $stmt->fetchColumn();
    }

    public function contarResponsaveisAtivos(int $idClube): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM membros_clube
             WHERE id_clube = :id_clube AND status = 'ativo' AND papel = 'responsavel'"
        );
        $stmt->execute([':id_clube' => $idClube]);

        return (int) $stmt->fetchColumn();
    }

    // Upsert atômico sem usar VALUES() (compatível com MySQL 9 e MariaDB) para reutilizar (id_clube, id_usuario).
    public function salvarVinculo(
        int $idClube,
        int $idUsuario,
        string $papel,
        string $status,
        ?int $convidadoPor = null
    ): void {
        $agora = date('Y-m-d H:i:s');
        $convidadoEm = $status === 'pendente' ? $agora : null;
        $entrouEm = $status === 'ativo' ? $agora : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO membros_clube (
                id_clube, id_usuario, papel, status, convidado_por, convidado_em, entrou_em
             ) VALUES (
                :id_clube, :id_usuario, :papel, :status, :convidado_por, :convidado_em, :entrou_em
             )
             ON DUPLICATE KEY UPDATE
                papel = :u_papel,
                status = :u_status,
                convidado_por = :u_convidado_por,
                convidado_em = :u_convidado_em,
                entrou_em = COALESCE(:u_entrou_em, entrou_em)'
        );
        $stmt->execute([
            ':id_clube' => $idClube,
            ':id_usuario' => $idUsuario,
            ':papel' => $papel,
            ':status' => $status,
            ':convidado_por' => $convidadoPor,
            ':convidado_em' => $convidadoEm,
            ':entrou_em' => $entrouEm,
            ':u_papel' => $papel,
            ':u_status' => $status,
            ':u_convidado_por' => $convidadoPor,
            ':u_convidado_em' => $convidadoEm,
            ':u_entrou_em' => $entrouEm,
        ]);
    }

    public function atualizarStatus(int $idClube, int $idUsuario, string $novoStatus, bool $marcarEntrada = false): void
    {
        if ($marcarEntrada) {
            $stmt = $this->pdo->prepare(
                'UPDATE membros_clube
                 SET status = :status, entrou_em = NOW()
                 WHERE id_clube = :id_clube AND id_usuario = :id_usuario'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE membros_clube
                 SET status = :status
                 WHERE id_clube = :id_clube AND id_usuario = :id_usuario'
            );
        }

        $stmt->execute([
            ':status' => $novoStatus,
            ':id_clube' => $idClube,
            ':id_usuario' => $idUsuario,
        ]);
    }

    public function removerConvitePendente(int $idClube, int $idUsuario): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM membros_clube
             WHERE id_clube = :id_clube AND id_usuario = :id_usuario AND status = 'pendente'"
        );
        $stmt->execute([
            ':id_clube' => $idClube,
            ':id_usuario' => $idUsuario,
        ]);
    }
}
