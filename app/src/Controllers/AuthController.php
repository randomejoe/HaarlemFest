<?php

namespace App\Controllers;

use App\Exceptions\UserConflictException;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\View;

class AuthController
{
    private UserRepository $users;
    private AuthService $auth;
    private CaptchaService $captcha;

    public function __construct(UserRepository $users, AuthService $auth, CaptchaService $captcha)
    {
        $this->users = $users;
        $this->auth = $auth;
        $this->captcha = $captcha;
    }

    public function showRegister(): void
    {
        $redirect = $this->sanitizeRedirect((string) ($_GET['redirect'] ?? '/'));
        echo View::render('register', ['redirect' => $redirect]);
    }

    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $captchaPayload = $_POST['altcha'] ?? '';
        $redirect = $this->sanitizeRedirect((string) ($_POST['redirect'] ?? '/'));

        if ($username === '' || $email === '' || $password === '') {
            echo View::render('register', [
                'error' => 'All fields are required.',
                'redirect' => $redirect,
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderRegisterError($redirect, 'Please provide a valid email address.');
            return;
        }

        $lengthError = $this->validateRegistrationLengths($username, $email);
        if ($lengthError !== null) {
            $this->renderRegisterError($redirect, $lengthError);
            return;
        }

        if (!$this->captcha->verify($captchaPayload)) {
            $this->renderRegisterError($redirect, 'Captcha verification failed.');
            return;
        }

        if ($this->users->findByEmail($email) || $this->users->findByUsername($username)) {
            $this->renderRegisterError($redirect, 'That email or username is already in use.');
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $userId = $this->users->create($username, $email, $passwordHash);
        } catch (UserConflictException $e) {
            $this->renderRegisterError($redirect, $e->getMessage());
            return;
        }

        $this->auth->login([
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'user',
        ]);
        header('Location: ' . $redirect);
        exit;
    }

    public function showLogin(): void
    {
        $redirect = $this->sanitizeRedirect((string) ($_GET['redirect'] ?? '/'));
        echo View::render('login', ['redirect' => $redirect]);
    }

    public function login(): void
    {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = $this->sanitizeRedirect((string) ($_POST['redirect'] ?? '/'));

        if ($identifier === '' || $password === '') {
            echo View::render('login', [
                'error' => 'Email/username and password are required.',
                'redirect' => $redirect,
            ]);
            return;
        }

        $user = null;
        if (str_contains($identifier, '@')) {
            $user = $this->users->findByEmail($identifier);
        } else {
            $user = $this->users->findByUsername($identifier);
        }

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            echo View::render('login', [
                'error' => 'Invalid credentials.',
                'redirect' => $redirect,
            ]);
            return;
        }

        $this->auth->login($user);
        header('Location: ' . $redirect);
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

    private function sanitizeRedirect(string $redirect): string
    {
        $redirect = trim($redirect);

        if ($redirect === '' || $redirect[0] !== '/') {
            return '/';
        }

        if (str_starts_with($redirect, '//')) {
            return '/';
        }

        if (str_contains($redirect, "\n") || str_contains($redirect, "\r")) {
            return '/';
        }

        return $redirect;
    }

    private function renderRegisterError(string $redirect, string $message): void
    {
        echo View::render('register', [
            'error' => $message,
            'redirect' => $redirect,
        ]);
    }

    private function validateRegistrationLengths(string $username, string $email): ?string
    {
        if ($this->textLength($username) > UserRepository::USERNAME_MAX_LENGTH) {
            return 'Username must be ' . UserRepository::USERNAME_MAX_LENGTH . ' characters or fewer.';
        }

        if ($this->textLength($email) > UserRepository::EMAIL_MAX_LENGTH) {
            return 'Email must be ' . UserRepository::EMAIL_MAX_LENGTH . ' characters or fewer.';
        }

        return null;
    }

    private function textLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}
