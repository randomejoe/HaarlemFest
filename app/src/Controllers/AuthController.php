<?php

namespace App\Controllers;

use App\Models\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CaptchaService;

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
        try {
            require(__DIR__ . '/../Views/register.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AuthController::showRegister error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function register(): void
    {
        try {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $captchaPayload = $_POST['altcha'] ?? '';
            $old = [
                'username' => $username,
                'email' => $email,
            ];

            $errors = User::validateRegistration($username, $email, $password);
            if ($errors !== []) {
                extract(['error' => $errors[0], 'old' => $old], EXTR_SKIP);
                require(__DIR__ . '/../Views/register.php');
                return;
            }

            if (!$this->captcha->verify($captchaPayload)) {
                extract(['error' => 'Captcha verification failed.', 'old' => $old], EXTR_SKIP);
                require(__DIR__ . '/../Views/register.php');
                return;
            }

            if ($this->users->findByEmail($email) || $this->users->findByUsername($username)) {
                extract(['error' => 'That email or username is already in use.', 'old' => $old], EXTR_SKIP);
                require(__DIR__ . '/../Views/register.php');
                return;
            }

            $passwordHash = $this->auth->hashPassword($password);
            $userId = $this->users->create($username, $email, $passwordHash);

            $this->auth->login(new User(
                id: $userId,
                username: $username,
                email: $email,
                role: UserRole::User
            ));
            header('Location: /');
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AuthController::register error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function showLogin(): void
    {
        try {
            require(__DIR__ . '/../Views/login.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AuthController::showLogin error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function login(): void
    {
        try {
            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $old = [
                'identifier' => $identifier,
            ];

            if ($identifier === '' || $password === '') {
                extract(['error' => 'Email/username and password are required.', 'old' => $old], EXTR_SKIP);
                require(__DIR__ . '/../Views/login.php');
                return;
            }

            $user = null;
            if (str_contains($identifier, '@')) {
                $user = $this->users->findByEmail($identifier);
            } else {
                $user = $this->users->findByUsername($identifier);
            }

            if (!$user || !$this->auth->verifyPassword($password, $user->passwordHash() ?? '')) {
                extract(['error' => 'Invalid credentials.', 'old' => $old], EXTR_SKIP);
                require(__DIR__ . '/../Views/login.php');
                return;
            }

            $this->auth->login($user);
            header('Location: /');
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AuthController::login error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function logout(): void
    {
        try {
            $this->auth->logout();
            header('Location: /login');
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AuthController::logout error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
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
