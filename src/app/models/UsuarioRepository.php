<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\ConnectionFactory;
use PDO;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

final class UsuarioRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionFactory::getConnection();
    }

    public function buscarPorId(int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, nome, apelido, email, status, criado_em
             FROM usuarios
             WHERE id_usuario = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idUsuario]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    // Inclui senha_hash porque esta consulta alimenta a verificação de login.
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, nome, apelido, email, senha_hash, status, criado_em
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => mb_strtolower(trim($email), 'UTF-8')]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function buscarPorApelido(string $apelido): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, nome, apelido, email, status, criado_em
             FROM usuarios
             WHERE apelido = :apelido
             LIMIT 1'
        );
        $stmt->execute([':apelido' => trim($apelido)]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function existeEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => mb_strtolower(trim($email), 'UTF-8')]);

        return $stmt->fetchColumn() !== false;
    }

    public function existeApelido(string $apelido): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM usuarios WHERE apelido = :apelido LIMIT 1'
        );
        $stmt->execute([':apelido' => trim($apelido)]);

        return $stmt->fetchColumn() !== false;
    }

    public function criar(string $nome, string $apelido, string $email, string $senhaHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, apelido, email, senha_hash, status)
             VALUES (:nome, :apelido, :email, :senha_hash, :status)'
        );
        $stmt->execute([
            ':nome' => trim($nome),
            ':apelido' => trim($apelido),
            ':email' => mb_strtolower(trim($email), 'UTF-8'),
            ':senha_hash' => $senhaHash,
            ':status' => 'ativo',
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
