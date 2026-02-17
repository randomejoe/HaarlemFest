<?php

namespace App\Services;

class AuthService
{
    public function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['username'] = (string) ($user['username'] ?? '');
        $_SESSION['email'] = (string) ($user['email'] ?? '');
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['logged_in_at'] = time();
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

    public function currentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'user_id' => (int) $_SESSION['user_id'],
            'username' => (string) ($_SESSION['username'] ?? ''),
            'email' => (string) ($_SESSION['email'] ?? ''),
            'role' => (string) ($_SESSION['role'] ?? 'user'),
            'logged_in_at' => (int) ($_SESSION['logged_in_at'] ?? 0),
        ];
    }
}
