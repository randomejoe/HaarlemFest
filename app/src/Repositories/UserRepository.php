<?php

namespace App\Repositories;

use App\Database\Connection;
use App\Models\User;
use App\Models\UserRole;
use PDO;

class UserRepository
{
    private PDO $pdo;
    private const USER_COLUMNS = 'user_id, username, email, role, password_hash, first_name, last_name, address, city, country, phone_number, created_at, password_reset_token, password_reset_expires_at';

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::USER_COLUMNS . ' FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return ($row = $stmt->fetch()) !== false ? User::fromArray($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::USER_COLUMNS . ' FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return ($row = $stmt->fetch()) !== false ? User::fromArray($row) : null;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::USER_COLUMNS . ' FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return ($row = $stmt->fetch()) !== false ? User::fromArray($row) : null;
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (role, username, email, password_hash, created_at) VALUES (:role, :username, :email, :password_hash, NOW())'
        );
        $stmt->execute([
            'role' => UserRole::User->value,
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

    public function findByResetTokenHash(string $tokenHash): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE password_reset_token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $tokenHash]);
        return ($row = $stmt->fetch()) !== false ? User::fromArray($row) : null;
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

    public function updateProfile(int $userId, array $profileData): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET username = :username,
                 first_name = :first_name,
                 last_name = :last_name,
                 address = :address,
                 city = :city,
                 country = :country,
                 phone_number = :phone_number
             WHERE user_id = :id'
        );

        $stmt->execute([
            'username' => (string) ($profileData['username'] ?? ''),
            'first_name' => $profileData['first_name'] ?? null,
            'last_name' => $profileData['last_name'] ?? null,
            'address' => $profileData['address'] ?? null,
            'city' => $profileData['city'] ?? null,
            'country' => $profileData['country'] ?? null,
            'phone_number' => $profileData['phone_number'] ?? null,
            'id' => $userId,
        ]);
    }

}
