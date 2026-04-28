<?php

namespace App\Models;

final class DeliveryResult
{
    public function __construct(
        private bool $success,
        private string $message,
        private ?string $emailWarning = null,
    ) {
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function emailWarning(): ?string
    {
        return $this->emailWarning;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'email_warning' => $this->emailWarning,
        ];
    }
}
