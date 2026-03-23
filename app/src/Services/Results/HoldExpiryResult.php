<?php

declare(strict_types=1);

namespace App\Services\Results;

/**
 * Value object representing the outcome of an expired-hold cleanup run.
 */
class HoldExpiryResult
{
    /**
     * @param int   $releasedCount    Number of ticket holds released.
     * @param int[] $expiredAttemptIds Checkout attempt IDs that were marked expired.
     * @param bool  $wasExecuted      True if cleanup actually ran, false if skipped.
     * @param ?string $skipReason     Reason why cleanup was skipped (e.g. "cooldown"), or null.
     */
    public function __construct(
        private int $releasedCount,
        private array $expiredAttemptIds,
        private bool $wasExecuted = true,
        private ?string $skipReason = null,
    ) {
    }

    public function getReleasedCount(): int
    {
        return $this->releasedCount;
    }

    /**
     * @return int[]
     */
    public function getExpiredAttemptIds(): array
    {
        return $this->expiredAttemptIds;
    }

    public function wasExecuted(): bool
    {
        return $this->wasExecuted;
    }

    public function getSkipReason(): ?string
    {
        return $this->skipReason;
    }

    /**
     * Convert back to the associative array structure currently used by
     * controllers/CLI scripts.
     */
    public function toArray(): array
    {
        return [
            'released_count'      => $this->releasedCount,
            'expired_attempt_ids' => $this->expiredAttemptIds,
            'was_executed'        => $this->wasExecuted,
            'skip_reason'         => $this->skipReason,
        ];
    }
}

