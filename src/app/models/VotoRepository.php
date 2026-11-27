<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class VotoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function buscarIndicacaoVotada(int $idCiclo, int $idUsuario): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_indicacao FROM votos WHERE id_ciclo = :id_ciclo AND id_usuario = :id_usuario LIMIT 1'
        );
        $stmt->execute([
            ':id_ciclo' => $idCiclo,
            ':id_usuario' => $idUsuario,
        ]);
        $val = $stmt->fetchColumn();

        return $val !== false ? (int) $val : null;
    }

    // Upsert atômico sem usar VALUES() (compatível com MySQL 9 e MariaDB).
    public function registrarOuTrocar(int $idCiclo, int $idClube, int $idIndicacao, int $idUsuario): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO votos (id_ciclo, id_clube, id_indicacao, id_usuario)
             VALUES (:id_ciclo, :id_clube, :id_indicacao, :id_usuario)
             ON DUPLICATE KEY UPDATE
                id_indicacao = :u_id_indicacao'
        );
        $stmt->execute([
            ':id_ciclo' => $idCiclo,
            ':id_clube' => $idClube,
            ':id_indicacao' => $idIndicacao,
            ':id_usuario' => $idUsuario,
            ':u_id_indicacao' => $idIndicacao,
        ]);
    }
}
