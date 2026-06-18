<?php

namespace App\Controllers;

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

        $phoneError = User::validatePhoneNumber($phoneNumber);
        if ($phoneError !== null) {
            $this->renderAccountError($submittedUser, $phoneError);
            return;
        }

        try {
            $updatedUser = $this->account->updateProfile($userId, [
                'username'     => $username,
                'first_name'   => $firstName !== '' ? $firstName : null,
                'last_name'    => $lastName !== '' ? $lastName : null,
                'address'      => $address !== '' ? $address : null,
                'city'         => $city !== '' ? $city : null,
                'country'      => $country !== '' ? $country : null,
                'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
            ]);
        } catch (\RuntimeException $e) {
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
        $fields = [
            ['Username',     User::USERNAME_MAX_LENGTH,     $user->username()],
            ['First name',   User::FIRST_NAME_MAX_LENGTH,   $user->firstName()],
            ['Last name',    User::LAST_NAME_MAX_LENGTH,    $user->lastName()],
            ['Address',      User::ADDRESS_MAX_LENGTH,      $user->address()],
            ['City',         User::CITY_MAX_LENGTH,         $user->city()],
            ['Country',      User::COUNTRY_MAX_LENGTH,      $user->country()],
            ['Phone number', User::PHONE_NUMBER_MAX_LENGTH, $user->phoneNumber()],
        ];

        foreach ($fields as [$label, $limit, $value]) {
            $value = trim((string) $value);
            if ($value !== '' && mb_strlen($value, 'UTF-8') > $limit) {
                return $label . ' must be ' . $limit . ' characters or fewer.';
            }
        }

        return null;
    }
}
