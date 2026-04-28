<?php

namespace App\Services;

use App\Exceptions\UserConflictException;
use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\Interfaces\IAccountService;
use RuntimeException;

class AccountService implements IAccountService
{
    private IUserRepository $users;

    public function __construct(IUserRepository $users)
    {
        $this->users = $users;
    }

    public function loadProfile(int $userId): ?User
    {
        return $this->users->findById($userId);
    }

    public function updateProfile(int $userId, array $profileData): User
    {
        if (
            isset($profileData['username'])
            && $this->isUsernameTakenByOther((string) $profileData['username'], $userId)
        ) {
            throw new UserConflictException('Username is already in use.');
        }

        $this->users->updateProfile($userId, $profileData);
        $refreshed = $this->users->findById($userId);

        if ($refreshed === null) {
            throw new RuntimeException('User disappeared after profile update.');
        }

        return $refreshed;
    }

    public function isUsernameTakenByOther(string $username, int $excludingUserId): bool
    {
        $existing = $this->users->findByUsername($username);

        return $existing !== null && $existing->getId() !== $excludingUserId;
    }
}
