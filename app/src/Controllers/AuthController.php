<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\View;

class AuthController
{
    private UserRepository $users;
    private AuthService $auth;
    private CaptchaService $captcha;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->auth = new AuthService();
        $this->captcha = new CaptchaService();
    }

    public function showRegister(): void
    {
        echo View::render('register');
    }

    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $captchaPayload = $_POST['altcha'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            echo View::render('register', ['error' => 'All fields are required.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo View::render('register', ['error' => 'Please provide a valid email address.']);
            return;
        }

        if (!$this->captcha->verify($captchaPayload)) {
            echo View::render('register', ['error' => 'Captcha verification failed.']);
            return;
        }

        if ($this->users->findByEmail($email) || $this->users->findByUsername($username)) {
            echo View::render('register', ['error' => 'That email or username is already in use.']);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->users->create($username, $email, $passwordHash);

        $this->auth->login(['user_id' => $userId, 'role' => 'user']);
        header('Location: /');
        exit;
    }

    public function showLogin(): void
    {
        echo View::render('login');
    }

    public function login(): void
    {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            echo View::render('login', ['error' => 'Email/username and password are required.']);
            return;
        }

        $user = null;
        if (str_contains($identifier, '@')) {
            $user = $this->users->findByEmail($identifier);
        } else {
            $user = $this->users->findByUsername($identifier);
        }

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            echo View::render('login', ['error' => 'Invalid credentials.']);
            return;
        }

        $this->auth->login($user);
        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }

    public function altchaChallenge(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        try {
            $challenge = $this->captcha->createChallenge();
            $payload = [
                'algorithm' => $challenge->algorithm,
                'challenge' => $challenge->challenge,
                'maxnumber' => $challenge->maxNumber,
                'salt' => $challenge->salt,
                'signature' => $challenge->signature,
            ];
            echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('ALTCHA challenge error: ' . $e->getMessage());
            echo json_encode(['error' => 'ALTCHA challenge failed.']);
        }
        exit;
    }
}
