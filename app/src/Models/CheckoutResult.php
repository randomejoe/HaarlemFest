<?php

namespace App\Models;

final class CheckoutResult
{
    /**
     * @param StockConflict[] $conflicts
     */
    public function __construct(
        private string $status,
        private string $message,
        private ?int $attemptId = null,
        private ?string $redirectUrl = null,
        private ?string $providerReference = null,
        private array $conflicts = [],
    ) {
    }

    public static function locked(int $attemptId): self
    {
        return new self('locked', 'Your planner is locked while payment is pending.', $attemptId);
    }

    public static function invalidRequest(string $message = 'This checkout request is no longer valid. Please try again.'): self
    {
        return new self('invalid_request', $message);
    }

    public static function emptyPlanner(): self
    {
        return new self('empty_planner', 'Your planner is empty.');
    }

    public static function invalidPlanner(string $message = 'Remove unavailable events from your planner before checkout.'): self
    {
        return new self('planner_invalid', $message);
    }

    /**
     * @param StockConflict[] $conflicts
     */
    public static function outOfStock(array $conflicts): self
    {
        return new self(
            'out_of_stock',
            'Some items are no longer available in the requested quantities.',
            conflicts: $conflicts
        );
    }

    public static function handoffCreated(int $attemptId, string $redirectUrl): self
    {
        return new self(
            'handoff_created',
            'Payment handoff created. Continue to pending payment status.',
            $attemptId,
            $redirectUrl
        );
    }

    public static function handoffFailed(string $message, ?int $attemptId = null): self
    {
        return new self('handoff_failed', $message, $attemptId);
    }

    public static function alreadyPending(int $attemptId, string $redirectUrl): self
    {
        return new self(
            'already_pending',
            'Payment is already pending for this checkout.',
            $attemptId,
            $redirectUrl
        );
    }

    public static function retryRequired(): self
    {
        return new self('retry_required', 'Please retry checkout. The previous attempt could not be completed.');
    }

    public static function alreadyPaid(int $attemptId): self
    {
        return new self('already_paid', 'This checkout attempt was already confirmed as paid.', $attemptId);
    }

    public static function alreadyProcessing(): self
    {
        return new self('already_processing', 'Checkout is already being processed. Please wait.');
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

    public function attemptId(): ?int
    {
        return $this->attemptId;
    }

    public function getAttemptId(): ?int
    {
        return $this->attemptId;
    }

    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function providerReference(): ?string
    {
        return $this->providerReference;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    /**
     * @return StockConflict[]
     */
    public function conflicts(): array
    {
        return $this->conflicts;
    }

    /**
     * @return StockConflict[]
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function isSuccess(): bool
    {
        return $this->status === 'handoff_created';
    }

    public function toArray(): array
    {
        $data = [
            'status' => $this->status,
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
            $data['conflicts'] = array_map(
                static fn (StockConflict $conflict): array => $conflict->toArray(),
                $this->conflicts
            );
        }

        return $data;
    }
}
