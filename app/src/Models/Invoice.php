<?php

namespace App\Models;

final class Invoice
{
    public function __construct(
        private array $data,
        private array $items = [],
        private ?int $checkoutAttemptId = null,
    ) {
    }

    public static function hydrate(array $row, array $items = [], ?int $checkoutAttemptId = null): self
    {
        return new self($row, $items, $checkoutAttemptId);
    }

    public function invoiceId(): int
    {
        return (int) ($this->data['invoice_id'] ?? 0);
    }

    public function userId(): ?int
    {
        $value = $this->data['user_id'] ?? null;
        return $value === null ? null : (int) $value;
    }

    public function invoiceNumber(): string
    {
        $value = $this->data['invoice_number'] ?? null;
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return 'INV-' . $this->invoiceId();
    }

    public function orderReference(): string
    {
        if ($this->checkoutAttemptId !== null && $this->checkoutAttemptId > 0) {
            return '#' . $this->checkoutAttemptId;
        }

        return (string) ($this->data['order_reference'] ?? '');
    }

    public function issuedAt(): string
    {
        return (string) ($this->data['issued_at'] ?? ($this->data['created_at'] ?? ''));
    }

    public function currency(): string
    {
        return strtoupper((string) ($this->data['currency'] ?? 'EUR'));
    }

    public function totalPrice(): float
    {
        return (float) ($this->data['total_price'] ?? 0);
    }

    public function totalTickets(): int
    {
        return (int) ($this->data['total_tickets'] ?? count($this->items));
    }

    public function items(): array
    {
        return $this->items;
    }

    public function checkoutAttemptId(): ?int
    {
        return $this->checkoutAttemptId;
    }

    public function toArray(): array
    {
        return array_merge($this->data, [
            'invoice_id' => $this->invoiceId(),
            'invoice_number' => $this->invoiceNumber(),
            'order_reference' => $this->orderReference(),
            'issued_at' => $this->issuedAt(),
            'currency' => $this->currency(),
            'total_price_value' => $this->totalPrice(),
            'total_tickets' => $this->totalTickets(),
            'items' => $this->items,
        ]);
    }
}
