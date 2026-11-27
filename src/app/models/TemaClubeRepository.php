<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class TemaClubeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function listarAtivos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id_tema, slug, nome, descricao
             FROM temas_clube
             WHERE ativo = 1
             ORDER BY nome ASC'
        );

        return $stmt ? $stmt->fetchAll() : [];
    }

    public function buscarPorId(int $idTema): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_tema, slug, nome, descricao, ativo
             FROM temas_clube
             WHERE id_tema = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idTema]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}
