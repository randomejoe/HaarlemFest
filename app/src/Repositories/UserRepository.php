<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (role, username, email, password_hash, created_at) VALUES (:role, :username, :email, :password_hash, NOW())'
        );
        $stmt->execute([
            'role' => 'user',
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function setPasswordResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_reset_token = :token, password_reset_expires_at = :expires_at WHERE user_id = :id'
        );
        $stmt->execute([
            'token' => $tokenHash,
            'expires_at' => $expiresAt,
            'id' => $userId,
        ]);
    }

    public function findByResetTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE password_reset_token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $tokenHash]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash, password_reset_token = NULL, password_reset_expires_at = NULL WHERE user_id = :id'
        );
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId,
        ]);
    }
}
