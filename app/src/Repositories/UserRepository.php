<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserRole;
use App\Repositories\Interfaces\IUserRepository;
use PDO;
use PDOException;

class UserRepository implements IUserRepository
{
    private PDO $pdo;
    private const USER_COLUMNS = 'user_id, username, email, role, password_hash, first_name, last_name, address, city, country, phone_number, created_at, password_reset_token, password_reset_expires_at';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (role, username, email, password_hash, created_at) VALUES (:role, :username, :email, :password_hash, NOW())'
            );
            $stmt->execute([
                'role' => 'user',
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
        } catch (PDOException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new \RuntimeException('That email or username is already in use.', 0, $e);
            }

            throw $e;
        }

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
        try {
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
        } catch (PDOException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new \RuntimeException('Username is already in use.', 0, $e);
            }

            throw $e;
        }
    }

    public function updateCheckoutDetails(int $userId, array $details): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 address = :address,
                 city = :city,
                 country = :country,
                 phone_number = :phone_number
             WHERE user_id = :id'
        );

        $stmt->execute([
            'first_name' => $details['first_name'] ?? null,
            'last_name' => $details['last_name'] ?? null,
            'address' => $details['address'] ?? null,
            'city' => $details['city'] ?? null,
            'country' => $details['country'] ?? null,
            'phone_number' => $details['phone_number'] ?? null,
            'id' => $userId,
        ]);
    }

    private function isUniqueConstraintViolation(PDOException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062;
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::USER_COLUMNS . ' FROM users');
        $stmt->execute();
        $results = $stmt->fetchAll();
        $users = [];

        foreach ($results as $user) {
            $users[] = User::fromArray($user);
        }
        return $users;
    }

    public function deleteUser(int $id): bool
    {

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->execute([
            'user_id' => $id
        ]);
        return true;
    }
}
