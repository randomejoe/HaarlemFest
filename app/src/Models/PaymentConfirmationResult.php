<?php

namespace App\Models;

final class PaymentConfirmationResult
{
    public function __construct(
        private string $status,
        private string $message,
        private ?int $invoiceId = null,
        private ?string $emailWarning = null,
    ) {
    }

    public static function paid(int $invoiceId, string $message = 'Payment confirmed.', ?string $emailWarning = null): self
    {
        return new self('paid', $message, $invoiceId, $emailWarning);
    }

    public static function notFound(string $message = 'Checkout attempt not found.'): self
    {
        return new self('not_found', $message);
    }

    public static function forbidden(string $message = 'You are not allowed to confirm this payment.'): self
    {
        return new self('forbidden', $message);
    }

    public static function alreadyPaid(string $message = 'Payment was already confirmed for this attempt.'): self
    {
        return new self('already_paid', $message);
    }

    public static function expired(string $message = 'This hold expired before payment confirmation.'): self
    {
        return new self('expired', $message);
    }

    public static function failed(string $message = 'This checkout handoff failed and cannot be confirmed.'): self
    {
        return new self('failed', $message);
    }

    public static function invalidStatus(string $message = 'This checkout attempt is not waiting for payment confirmation.'): self
    {
        return new self('invalid_status', $message);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function invoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function getInvoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function emailWarning(): ?string
    {
        return $this->emailWarning;
    }

    public function getEmailWarning(): ?string
    {
        return $this->emailWarning;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function toArray(): array
    {
        $data = [
            'status' => $this->status,
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
