<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Interfaces\IPasswordResetService;
use App\View;

class PasswordController
{
    private IPasswordResetService $reset;

    public function __construct(IPasswordResetService $reset)
    {
        $this->reset = $reset;
    }

    public function showForgot(): void
    {
        echo View::render('forgot');
    }

    public function sendReset(): void
    {
        $email = trim($_POST['email'] ?? '');
        if ($email !== '') {
            $this->reset->requestReset($email);
        }

        echo View::render('forgot', [
            'message' => 'If that address is in our system, you will receive a reset link shortly.',
            'old' => ['email' => $email],
        ]);
    }

    public function showReset(string $token = ''): void
    {
        echo View::render('reset', ['token' => $token]);
    }

    public function reset(string $token = ''): void
    {
        $password = $_POST['password'] ?? '';

        if ($token === '' || $password === '') {
            echo View::render('reset', ['token' => $token, 'error' => 'Password is required.']);
            return;
        }

        $errors = User::validatePassword($password);
        if ($errors !== []) {
            echo View::render('reset', ['token' => $token, 'error' => $errors[0]]);
            return;
        }

        $ok = $this->reset->resetPassword($token, $password);
        if (!$ok) {
            echo View::render('reset', ['token' => $token, 'error' => 'Invalid or expired reset link.']);
            return;
        }

        echo View::render('login', ['message' => 'Password updated. Please log in.']);
    }
}
