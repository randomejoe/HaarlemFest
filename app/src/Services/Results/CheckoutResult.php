<?php

declare(strict_types=1);

namespace App\Services\Results;

/**
 * Value object representing the outcome of a checkout confirmation attempt.
 *
 * Typical statuses include (but are not limited to):
 * - locked
 * - invalid_request
 * - planner_empty
 * - planner_invalid
 * - handoff_created
 * - handoff_failed
 * - already_pending
 * - retry_required
 * - already_paid
 * - already_processing
 */
class CheckoutResult
{
    public function __construct(
        private string $status,
        private string $message,
        private ?int $attemptId = null,
        private ?string $redirectUrl = null,
        private ?string $providerReference = null,
        private array $conflicts = [],
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

    public function getAttemptId(): ?int
    {
        return $this->attemptId;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    /**
     * Return any stock conflicts that occurred during checkout.
     *
     * This is typically a list of arrays describing which events or tickets
     * could not be reserved due to insufficient availability.
     *
     * @return array[]
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    /**
     * Indicates whether the checkout was successfully handed off to the
     * payment provider.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'handoff_created';
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

        if ($this->attemptId !== null) {
            $data['attempt_id'] = $this->attemptId;
        }

        if ($this->redirectUrl !== null) {
            $data['redirect_url'] = $this->redirectUrl;
        }

        if ($this->providerReference !== null) {
            $data['provider_reference'] = $this->providerReference;
        }

        if ($this->conflicts !== []) {
            $data['conflicts'] = $this->conflicts;
        }

        return $data;
    }
}

