<?php

namespace App\Controllers;

use App\Exceptions\UserConflictException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\View;

class AccountController
{
    private UserRepository $users;
    private AuthService $auth;

    public function __construct(UserRepository $users, AuthService $auth)
    {
        $this->users = $users;
        $this->auth = $auth;
    }

    public function show(): void
    {
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

        echo View::render('account', [
            'user' => $user,
            'updated' => isset($_GET['updated']),
        ]);
    }

    public function update(): void
    {
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
        $submittedUser = $this->buildSubmittedUser($userId, $sessionUser, [
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'phone_number' => $phoneNumber,
        ]);

        $errors = User::validateProfileUpdate($username);
        if ($errors !== []) {
            $this->renderAccountError($submittedUser, $errors[0]);
            return;
        }

        $lengthError = $this->validateProfileLengths($submittedUser);
        if ($lengthError !== null) {
            $this->renderAccountError($submittedUser, $lengthError);
            return;
        }

        $existing = $this->users->findByUsername($username);
        if ($existing !== null && $existing->id() !== $userId) {
            $this->renderAccountError($submittedUser, 'Username is already in use.');
            return;
        }

        try {
            $this->users->updateProfile($userId, [
                'username' => $username,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'address' => $address !== '' ? $address : null,
                'city' => $city !== '' ? $city : null,
                'country' => $country !== '' ? $country : null,
                'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
            ]);
        } catch (UserConflictException $e) {
            $this->renderAccountError($submittedUser, $e->getMessage());
            return;
        }

        $updatedUser = $this->users->findById($userId);
        if ($updatedUser !== null) {
            $this->auth->syncCurrentUser($updatedUser);
        }

        header('Location: /account?updated=1');
        exit;
    }

    private function renderAccountError(User $user, string $message): void
    {
        echo View::render('account', [
            'user' => $user,
            'error' => $message,
        ]);
    }

    private function buildSubmittedUser(int $userId, User $sessionUser, array $submitted): User
    {
        return new User(
            id: $userId,
            username: (string) ($submitted['username'] ?? ''),
            email: $sessionUser->email(),
            role: $sessionUser->role(),
            firstName: $submitted['first_name'] !== '' ? $submitted['first_name'] : null,
            lastName: $submitted['last_name'] !== '' ? $submitted['last_name'] : null,
            address: $submitted['address'] !== '' ? $submitted['address'] : null,
            city: $submitted['city'] !== '' ? $submitted['city'] : null,
            country: $submitted['country'] !== '' ? $submitted['country'] : null,
            phoneNumber: $submitted['phone_number'] !== '' ? $submitted['phone_number'] : null,
        );
    }

    private function validateProfileLengths(User $user): ?string
    {
        $limits = [
            'username' => UserRepository::USERNAME_MAX_LENGTH,
            'first_name' => UserRepository::FIRST_NAME_MAX_LENGTH,
            'last_name' => UserRepository::LAST_NAME_MAX_LENGTH,
            'address' => UserRepository::ADDRESS_MAX_LENGTH,
            'city' => UserRepository::CITY_MAX_LENGTH,
            'country' => UserRepository::COUNTRY_MAX_LENGTH,
            'phone_number' => UserRepository::PHONE_NUMBER_MAX_LENGTH,
        ];

        $labels = [
            'username' => 'Username',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'address' => 'Address',
            'city' => 'City',
            'country' => 'Country',
            'phone_number' => 'Phone number',
        ];

        foreach ($limits as $field => $limit) {
            $value = trim((string) match ($field) {
                'username' => $user->username(),
                'first_name' => $user->firstName(),
                'last_name' => $user->lastName(),
                'address' => $user->address(),
                'city' => $user->city(),
                'country' => $user->country(),
                'phone_number' => $user->phoneNumber(),
            });
            if ($value !== '' && $this->textLength($value) > $limit) {
                return $labels[$field] . ' must be ' . $limit . ' characters or fewer.';
            }
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
