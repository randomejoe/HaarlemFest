<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Models\UserRole;

class AuthService
{
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
            'user_id' => (int) $_SESSION['user_id'],
            'username' => (string) ($_SESSION['username'] ?? ''),
            'email' => (string) ($_SESSION['email'] ?? ''),
            'role' => $role->value,
        ]);
    }

    public function hashPassword(string $plaintext): string
    {
        $hash = password_hash($plaintext, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new AuthException('Failed to hash password.');
        }

        return $hash;
    }

    public function verifyPassword(string $plaintext, string $hash): bool
    {
        return password_verify($plaintext, $hash);
    }

    private function storeSessionUser(User $user): void
    {
        $sessionUser = $user->toSessionArray();
        $_SESSION['user_id'] = $sessionUser['user_id'];
        $_SESSION['username'] = $sessionUser['username'];
        $_SESSION['email'] = $sessionUser['email'];
        $_SESSION['role'] = $sessionUser['role'];
    }
}
