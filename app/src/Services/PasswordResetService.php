<?php

namespace App\Services;

use App\Config;
use App\Repositories\UserRepository;
use DateTime;
use Throwable;

class PasswordResetService
{
    private UserRepository $users;
    private Mailer $mailer;

    public function __construct(UserRepository $users, Mailer $mailer)
    {
        $this->users = $users;
        $this->mailer = $mailer;
    }

    public function requestReset(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $this->users->setPasswordResetToken((int) $user['user_id'], $tokenHash, $expiresAt);

        $baseUrl = rtrim(Config::env('APP_BASE_URL', 'http://localhost'), '/');
        $resetUrl = $baseUrl . '/password/reset/' . $token;

        $subject = 'Reset your password';
        $body = '<p>We received a request to reset your password.</p>'
            . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset password</a></p>'
            . '<p>This link expires in 1 hour.</p>';

        try {
            $this->mailer->send($user['email'], $user['username'], $subject, $body);
        } catch (Throwable $e) {
            error_log('Password reset email delivery failed for user ' . (int) $user['user_id'] . ': ' . $e->getMessage());
        }
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $tokenHash = hash('sha256', $token);
        $user = $this->users->findByResetTokenHash($tokenHash);
        if (!$user) {
            return false;
        }

        $expiresAt = $user['password_reset_expires_at'] ?? null;
        if (!$expiresAt || strtotime($expiresAt) < time()) {
            return false;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->users->updatePassword((int) $user['user_id'], $passwordHash);
        return true;
    }
}
