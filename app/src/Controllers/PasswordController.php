<?php

namespace App\Controllers;

use App\Services\PasswordResetService;
use App\View;

class PasswordController
{
    private PasswordResetService $reset;

    public function __construct()
    {
        $this->reset = new PasswordResetService();
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
            'message' => 'If that address is in our system, you will receive a reset link shortly.'
        ]);
    }

    public function showReset(array $vars = []): void
    {
        $token = $vars['token'] ?? '';
        echo View::render('reset', ['token' => $token]);
    }

    public function reset(array $vars = []): void
    {
        $token = $vars['token'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($token === '' || $password === '') {
            echo View::render('reset', ['token' => $token, 'error' => 'Password is required.']);
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
