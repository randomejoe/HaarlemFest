<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\Interfaces\IAuthService;
use RuntimeException;

class AuthService implements IAuthService
{
    private IUserRepository $users;

    public function __construct(IUserRepository $users)
    {
        $this->users = $users;
    }

    public function login(User $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $this->storeSessionUser($user);
        $_SESSION['logged_in_at'] = time();
    }

    public function syncCurrentUser(User $user): void
    {
        $this->storeSessionUser($user);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function currentUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $role = UserRole::tryFrom((string) ($_SESSION['role'] ?? UserRole::User->value)) ?? UserRole::User;

        return User::fromArray([
            'user_id'  => (int) $_SESSION['user_id'],
            'username' => (string) ($_SESSION['username'] ?? ''),
            'email'    => (string) ($_SESSION['email'] ?? ''),
            'role'     => $role->value,
            'enabled'  => (bool) ($_SESSION['enabled'] ?? false),
        ]);
    }

    public function hashPassword(string $plaintext): string
    {
        $hash = password_hash($plaintext, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('Failed to hash password.');
        }

        return $hash;
    }

    public function verifyPassword(string $plaintext, string $hash): bool
    {
        return password_verify($plaintext, $hash);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->users->findByUsername($username);
    }

    public function findByIdentifier(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) {
            return $this->findByEmail($identifier);
        }

        return $this->findByUsername($identifier);
    }

    public function registerUser(string $username, string $email, string $plaintextPassword): User
    {
        if ($this->findByEmail($email) !== null || $this->findByUsername($username) !== null) {
            throw new RuntimeException('That email or username is already in use.');
        }

        $newId = $this->users->create($username, $email, $this->hashPassword($plaintextPassword));

        return new User(
            id: $newId,
            username: $username,
            email: $email,
            role: UserRole::User
        );
    }

    private function storeSessionUser(User $user): void
    {
        $sessionUser = $user->toSessionArray();
        $_SESSION['user_id'] = $sessionUser['user_id'];
        $_SESSION['username'] = $sessionUser['username'];
        $_SESSION['email'] = $sessionUser['email'];
        $_SESSION['role'] = $sessionUser['role'];
        $_SESSION['enabled'] = $sessionUser['enabled'];
    }
}
