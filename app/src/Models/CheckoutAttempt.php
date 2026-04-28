<?php

namespace App\Models;

final class CheckoutAttempt
{
    public function __construct(private array $data)
    {
    }

    public static function hydrate(array $row): self
    {
        return new self($row);
    }

    public function id(): int
    {
        return (int) ($this->data['checkout_attempt_id'] ?? 0);
    }

    public function userId(): ?int
    {
        $value = $this->data['user_id'] ?? null;
        return $value === null ? null : (int) $value;
    }

    public function status(): string
    {
        return (string) ($this->data['status'] ?? '');
    }

    public function holdExpiresAt(): string
    {
        return (string) ($this->data['hold_expires_at'] ?? '');
    }

    public function totalPrice(): float
    {
        return (float) ($this->data['total_price'] ?? 0);
    }

    public function currency(): string
    {
        return (string) ($this->data['currency'] ?? 'EUR');
    }

    public function plannerToken(): string
    {
        return (string) ($this->data['planner_token'] ?? '');
    }

    public function paymentProvider(): ?string
    {
        $value = $this->data['payment_provider'] ?? null;
        return $value === null ? null : (string) $value;
    }

    public function paymentReference(): ?string
    {
        $value = $this->data['payment_reference'] ?? null;
        return $value === null ? null : (string) $value;
    }

    public function errorCode(): ?string
    {
        $value = $this->data['error_code'] ?? null;
        return $value === null ? null : (string) $value;
    }

    public function errorMessage(): ?string
    {
        $value = $this->data['error_message'] ?? null;
        return $value === null ? null : (string) $value;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
