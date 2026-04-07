<?php

namespace App\Models;

class User
{
    private const PASSWORD_MIN_LENGTH = 12;

    public function __construct(
        private int $id,
        private string $username,
        private string $email,
        private UserRole $role = UserRole::User,
        private ?string $passwordHash = null,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $address = null,
        private ?string $city = null,
        private ?string $country = null,
        private ?string $phoneNumber = null,
        private ?string $createdAt = null,
        private ?string $passwordResetToken = null,
        private ?string $passwordResetExpiresAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        try {
            $role = UserRole::from((string) ($data['role'] ?? 'user'));
        } catch (\ValueError) {
            $role = UserRole::User;
        }

        return new self(
            id: (int) ($data['user_id'] ?? 0),
            username: (string) ($data['username'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            role: $role,
            passwordHash: isset($data['password_hash']) ? (string) $data['password_hash'] : null,
            firstName: isset($data['first_name']) && $data['first_name'] !== '' ? (string) $data['first_name'] : null,
            lastName: isset($data['last_name']) && $data['last_name'] !== '' ? (string) $data['last_name'] : null,
            address: isset($data['address']) && $data['address'] !== '' ? (string) $data['address'] : null,
            city: isset($data['city']) && $data['city'] !== '' ? (string) $data['city'] : null,
            country: isset($data['country']) && $data['country'] !== '' ? (string) $data['country'] : null,
            phoneNumber: isset($data['phone_number']) && $data['phone_number'] !== '' ? (string) $data['phone_number'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            passwordResetToken: isset($data['password_reset_token']) ? (string) $data['password_reset_token'] : null,
            passwordResetExpiresAt: isset($data['password_reset_expires_at']) ? (string) $data['password_reset_expires_at'] : null
        );
    }

    public function toSessionArray(): array
    {
        return [
            'user_id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role->value,
        ];
    }

    public static function validateRegistration(string $username, string $email, string $password): array
    {
        $errors = [];

        if ($username === '') {
            $errors[] = 'Username is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        $errors = array_merge($errors, self::validatePassword($password));

        return $errors;
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];

        if ($password === '') {
            $errors[] = 'Password is required.';
            return $errors;
        }

        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must include at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must include at least one lowercase letter.';
        }

        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must include at least one number.';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must include at least one special character.';
        }

        return $errors;
    }

    public static function validateProfileUpdate(string $username): array
    {
        $errors = [];

        if ($username === '') {
            $errors[] = 'Username is required.';
        }

        return $errors;
    }

    public function getId(): int
    {
        return $this->id;
    }

    // For cms system
    public function getName(): string
    {
        return $this->username;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function passwordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function passwordResetExpiresAt(): ?string
    {
        return $this->passwordResetExpiresAt;
    }
}
