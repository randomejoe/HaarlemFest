<?php

namespace App\Controllers;

use App\Exceptions\UserConflictException;
use App\Models\User;
use App\Services\Interfaces\IAccountService;
use App\Services\Interfaces\IAuthService;
use App\View;

class AccountController
{
    private IAccountService $account;
    private IAuthService $auth;

    public function __construct(IAccountService $account, IAuthService $auth)
    {
        $this->account = $account;
        $this->auth = $auth;
    }

    public function show(): void
    {
        $sessionUser = $this->auth->currentUser();
        if ($sessionUser === null) {
            header('Location: /login');
            exit;
        }

        $user = $this->account->loadProfile($sessionUser->getId());
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

        $userId = $sessionUser->getId();

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

        try {
            $updatedUser = $this->account->updateProfile($userId, [
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

        $this->auth->syncCurrentUser($updatedUser);

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
            'username' => User::USERNAME_MAX_LENGTH,
            'first_name' => User::FIRST_NAME_MAX_LENGTH,
            'last_name' => User::LAST_NAME_MAX_LENGTH,
            'address' => User::ADDRESS_MAX_LENGTH,
            'city' => User::CITY_MAX_LENGTH,
            'country' => User::COUNTRY_MAX_LENGTH,
            'phone_number' => User::PHONE_NUMBER_MAX_LENGTH,
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
