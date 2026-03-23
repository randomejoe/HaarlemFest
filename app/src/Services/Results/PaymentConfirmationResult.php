<?php

declare(strict_types=1);

namespace App\Services\Results;

/**
 * Value object representing the outcome of confirming a pending payment.
 *
 * Typical statuses include (but are not limited to):
 * - paid
 * - not_found
 * - forbidden
 * - already_paid
 * - expired
 * - failed
 * - invalid_status
 */
class PaymentConfirmationResult
{
    public function __construct(
        private string $status,
        private string $message,
        private ?int $invoiceId = null,
        private ?string $emailWarning = null,
    ) {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getInvoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function getEmailWarning(): ?string
    {
        return $this->emailWarning;
    }

    /**
     * Indicates whether the payment was fully confirmed and marked as paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Convert back to the associative array structure currently used by
     * controllers/views. Optional fields are only included when non-null.
     */
    public function toArray(): array
    {
        $data = [
            'status'  => $this->status,
            'message' => $this->message,
        ];

        if ($this->invoiceId !== null) {
            $data['invoice_id'] = $this->invoiceId;
        }

        if ($this->emailWarning !== null) {
            $data['email_warning'] = $this->emailWarning;
        }

        return $data;
    }
}

