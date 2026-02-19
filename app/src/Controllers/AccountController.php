<?php

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;

class AccountController
{
    private UserRepository $users;
    private AuthService $auth;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->auth = new AuthService();
    }

    public function show(): void
    {
        try {
            $sessionUser = $this->auth->currentUser();
            if ($sessionUser === null) {
                header('Location: /login');
                exit;
            }

            $user = $this->users->findById($sessionUser->id());
            if ($user === null) {
                $this->auth->logout();
                header('Location: /login');
                exit;
            }

            extract([
                'user' => $user,
                'updated' => isset($_GET['updated']),
            ], EXTR_SKIP);
            require(__DIR__ . '/../Views/account.php');
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AccountController::show error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    public function update(): void
    {
        try {
            $sessionUser = $this->auth->currentUser();
            if ($sessionUser === null) {
                header('Location: /login');
                exit;
            }

            $userId = $sessionUser->id();

            $username = trim($_POST['username'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $phoneNumber = trim($_POST['phone_number'] ?? '');

            $errors = User::validateProfileUpdate($username);
            if ($errors !== []) {
                $user = $this->users->findById($userId);
                extract([
                    'user' => $this->fallbackUser($userId, $sessionUser, $user),
                    'error' => $errors[0],
                    'updated' => false,
                ], EXTR_SKIP);
                require(__DIR__ . '/../Views/account.php');
                return;
            }

            $existing = $this->users->findByUsername($username);
            if ($existing !== null && $existing->id() !== $userId) {
                $user = $this->users->findById($userId);
                extract([
                    'user' => $this->fallbackUser($userId, $sessionUser, $user),
                    'error' => 'Username is already in use.',
                    'updated' => false,
                ], EXTR_SKIP);
                require(__DIR__ . '/../Views/account.php');
                return;
            }

            $this->users->updateProfile($userId, [
                'username' => $username,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'address' => $address !== '' ? $address : null,
                'city' => $city !== '' ? $city : null,
                'country' => $country !== '' ? $country : null,
                'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
            ]);

            $updatedUser = $this->users->findById($userId);
            if ($updatedUser !== null) {
                $this->auth->syncCurrentUser($updatedUser);
            }

            header('Location: /account?updated=1');
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('AccountController::update error: ' . $e->getMessage());
            require(__DIR__ . '/../Views/error.php');
        }
    }

    private function fallbackUser(int $userId, User $sessionUser, ?User $storedUser): User
    {
        if ($storedUser !== null) {
            return $storedUser;
        }

        return new User(
            id: $userId,
            username: $sessionUser->username(),
            email: $sessionUser->email(),
            role: $sessionUser->role()
        );
    }

}
