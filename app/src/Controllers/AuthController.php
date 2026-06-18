<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\CaptchaService;
use App\Services\Interfaces\IAuthService;
use App\View;

class AuthController
{
    private IAuthService $auth;
    private CaptchaService $captcha;

    public function __construct(IAuthService $auth, CaptchaService $captcha)
    {
        $this->auth = $auth;
        $this->captcha = $captcha;
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
        $prefill = ['identifier' => $identifier];

        if ($identifier === '' || $password === '') {
            $this->renderLoginError($redirect, 'Email/username and password are required.', $prefill);
            return;
        }

        $user = $this->auth->findByIdentifier($identifier);

        if (!$user || !$this->auth->verifyPassword($password, $user->passwordHash() ?? '')) {
            $this->renderLoginError($redirect, 'Invalid credentials.', $prefill);
            return;
        }

        if (!$user->isEnabled()) {
            $this->renderLoginError($redirect, 'That account has been disabled.', $prefill);
            return;
        }

        $this->auth->login($user);
        header('Location: ' . $redirect);
        exit;
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
        $prefill = [
            'username' => $username,
            'email' => $email,
        ];

        $errors = User::validateRegistration($username, $email, $password);

        if ($errors !== []) {
            $this->renderRegisterErrors($redirect, $errors, $prefill);
            return;
        }

        if (!$this->captcha->verify($captchaPayload)) {
            $this->renderRegisterError($redirect, 'Captcha verification failed.', $prefill);
            return;
        }

        try {
            $user = $this->auth->registerUser($username, $email, $password);
        } catch (\RuntimeException $e) {
            $this->renderRegisterError($redirect, $e->getMessage(), $prefill);
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

        $challenge = $this->captcha->createChallenge();
        $payload = [
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'maxnumber' => $challenge->maxNumber,
            'salt' => $challenge->salt,
            'signature' => $challenge->signature,
        ];
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
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

    private function renderRegisterError(string $redirect, string $message, array $prefill = []): void
    {
        $this->renderRegisterErrors($redirect, [$message], $prefill);
    }

    private function renderRegisterErrors(string $redirect, array $errors, array $prefill = []): void
    {
        echo View::render('register', [
            'errors'   => $errors,
            'prefill'  => $prefill,
            'redirect' => $redirect,
        ]);
    }

    private function renderLoginError(string $redirect, string $message, array $prefill = []): void
    {
        echo View::render('login', [
            'error'    => $message,
            'prefill'  => $prefill,
            'redirect' => $redirect,
        ]);
    }

}
