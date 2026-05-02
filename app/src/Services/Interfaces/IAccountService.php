<?php

namespace App\Services\Interfaces;

use App\Models\User;

interface IAccountService
{
    public function loadProfile(int $userId): ?User;

    public function updateProfile(int $userId, array $profileData): User;

    public function isUsernameTakenByOther(string $username, int $excludingUserId): bool;
}
