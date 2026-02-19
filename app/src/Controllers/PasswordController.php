<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\PasswordResetService;

class PasswordController
{
    private PasswordResetService $reset;

    public function __construct()
    {
        $this->reset = new PasswordResetService();
    }

    public function showForgot(): void
    {
        try {
            require(__DIR__ . '/../Views/forgot.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('PasswordController::showForgot error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function sendReset(): void
    {
        try {
            $email = trim($_POST['email'] ?? '');
            if ($email !== '') {
                $this->reset->requestReset($email);
            }

            extract([
                'message' => 'If that address is in our system, you will receive a reset link shortly.',
                'old' => ['email' => $email],
            ], EXTR_SKIP);
            require(__DIR__ . '/../Views/forgot.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('PasswordController::sendReset error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function showReset(array $vars = []): void
    {
        try {
            $token = $vars['token'] ?? '';
            extract(['token' => $token], EXTR_SKIP);
            require(__DIR__ . '/../Views/reset.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('PasswordController::showReset error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function reset(array $vars = []): void
    {
        try {
            $token = $vars['token'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($token === '' || $password === '') {
                extract(['token' => $token, 'error' => 'Password is required.'], EXTR_SKIP);
                require(__DIR__ . '/../Views/reset.php');
                return;
            }

            $errors = User::validatePassword($password);
            if ($errors !== []) {
                extract(['token' => $token, 'error' => $errors[0]], EXTR_SKIP);
                require(__DIR__ . '/../Views/reset.php');
                return;
            }

            $ok = $this->reset->resetPassword($token, $password);
            if (!$ok) {
                extract(['token' => $token, 'error' => 'Invalid or expired reset link.'], EXTR_SKIP);
                require(__DIR__ . '/../Views/reset.php');
                return;
            }

            extract(['message' => 'Password updated. Please log in.'], EXTR_SKIP);
            require(__DIR__ . '/../Views/login.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('PasswordController::reset error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }
}
