<?php

namespace App\Services;

class AuthService
{
    public function login(array $user): void
    {
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['role'] = $user['role'] ?? 'user';
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
}
