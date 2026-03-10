<?php

namespace App\Services;

class CsrfService
{
    private const SESSION_KEY = 'csrf_token';

    public function getToken(): string
    {
        if (!$this->hasToken()) {
            $_SESSION[self::SESSION_KEY] = $this->generateToken();
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function validate(?string $token): bool
    {
        $submittedToken = trim((string) $token);
        if ($submittedToken === '') {
            return false;
        }

        return hash_equals($this->getToken(), $submittedToken);
    }

    public function rotateToken(): string
    {
        $_SESSION[self::SESSION_KEY] = $this->generateToken();
        return (string) $_SESSION[self::SESSION_KEY];
    }

    private function hasToken(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]) && is_string($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY] !== '';
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
