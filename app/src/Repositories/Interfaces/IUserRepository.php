<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface IUserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function create(string $username, string $email, string $passwordHash): int;

    public function updateProfile(int $userId, array $profileData): void;

    public function updateCheckoutDetails(int $userId, array $details): void;
}
