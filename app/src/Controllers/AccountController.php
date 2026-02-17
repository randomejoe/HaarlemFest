<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\View;

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
        $sessionUser = $this->auth->currentUser();
        if ($sessionUser === null) {
            header('Location: /login');
            exit;
        }

        $user = $this->users->findById((int) $sessionUser['user_id']);
        if ($user === null) {
            $this->auth->logout();
            header('Location: /login');
            exit;
        }

        echo View::render('account', ['user' => $user]);
    }

    public function update(): void
    {
        $sessionUser = $this->auth->currentUser();
        if ($sessionUser === null) {
            header('Location: /login');
            exit;
        }

        $userId = (int) $sessionUser['user_id'];

        $username = trim($_POST['username'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');

        if ($username === '') {
            $user = $this->users->findById($userId);
            echo View::render('account', [
                'user' => $user ?: ['user_id' => $userId, 'email' => $sessionUser['email']],
                'error' => 'Username is required.',
            ]);
            return;
        }

        $existing = $this->users->findByUsername($username);
        if ($existing !== null && (int) $existing['user_id'] !== $userId) {
            $user = $this->users->findById($userId);
            echo View::render('account', [
                'user' => $user ?: ['user_id' => $userId, 'email' => $sessionUser['email']],
                'error' => 'Username is already in use.',
            ]);
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

        $_SESSION['username'] = $username;

        header('Location: /account?updated=1');
        exit;
    }
}
