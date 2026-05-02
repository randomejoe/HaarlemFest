<?php

namespace App\Services\Interfaces;

interface IPasswordResetService
{
    public function requestReset(string $email): void;

    public function resetPassword(string $token, string $newPassword): bool;
}
