<?php

namespace App\Services;

use App\Config;
use App\Exceptions\AuthException;
use App\Repositories\UserRepository;
use DateTime;
use Throwable;

class PasswordResetService
{
    private UserRepository $users;
    private Mailer $mailer;
    private AuthService $auth;

    public function __construct(UserRepository $users, Mailer $mailer, AuthService $auth)
    {
        $this->users = $users;
        $this->mailer = $mailer;
        $this->auth = $auth;
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

        try {
            $this->users->setPasswordResetToken($user->getId(), $tokenHash, $expiresAt);
        } catch (\Throwable $e) {
            throw new AuthException('Failed to store reset token.', 0, $e);
        }

        $baseUrl = rtrim(Config::env('APP_BASE_URL', 'http://localhost'), '/');
        $resetUrl = $baseUrl . '/password/reset/' . $token;

        $subject = 'Reset your password';
        $body = '<p>We received a request to reset your password.</p>'
            . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset password</a></p>'
            . '<p>This link expires in 1 hour.</p>';

        try {
            $this->mailer->send($user->email(), $user->username(), $subject, $body);
        } catch (Throwable $e) {
            error_log('Password reset email delivery failed for user ' . $user->getId() . ': ' . $e->getMessage());
        }
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $tokenHash = hash('sha256', $token);
        $user = $this->users->findByResetTokenHash($tokenHash);
        if (!$user) {
            return false;
        }

        $expiresAt = $user->passwordResetExpiresAt();
        if (!$expiresAt || strtotime($expiresAt) < time()) {
            return false;
        }

        $passwordHash = $this->auth->hashPassword($newPassword);

        try {
            $this->users->updatePassword($user->getId(), $passwordHash);
        } catch (\Throwable $e) {
            throw new AuthException('Failed to update password.', 0, $e);
        }

        return true;
    }
}
