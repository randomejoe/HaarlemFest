<?php

namespace App\Services\Interfaces;

use App\Models\User;

interface IAuthService
{
    public function login(User $user): void;

    public function syncCurrentUser(User $user): void;

    public function logout(): void;

    public function isLoggedIn(): bool;

    public function currentUser(): ?User;

    public function hashPassword(string $plaintext): string;

    public function verifyPassword(string $plaintext, string $hash): bool;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function findByIdentifier(string $identifier): ?User;

    public function registerUser(string $username, string $email, string $plaintextPassword): User;
}
