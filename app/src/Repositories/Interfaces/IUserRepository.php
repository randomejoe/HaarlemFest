<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface IUserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function create(string $username, string $email, string $passwordHash): int;

    public function setPasswordResetToken(int $userId, string $tokenHash, string $expiresAt): void;

    public function findByResetTokenHash(string $tokenHash): ?User;

    public function updatePassword(int $userId, string $passwordHash): void;

    public function updateProfile(int $userId, array $profileData): void;

    public function updateCheckoutDetails(int $userId, array $details): void;

    public function getAllUsers(): array;

    public function deleteUser(int $id): bool;
}
