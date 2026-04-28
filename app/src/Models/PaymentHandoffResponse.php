<?php

namespace App\Models;

final class PaymentHandoffResponse
{
    public function __construct(
        private bool $success,
        private ?string $providerReference = null,
        private ?string $redirectUrl = null,
        private ?string $errorCode = null,
        private ?string $errorMessage = null,
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

    public function providerReference(): ?string
    {
        return $this->providerReference;
    }

    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider_reference' => $this->providerReference,
            'redirect_url' => $this->redirectUrl,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
