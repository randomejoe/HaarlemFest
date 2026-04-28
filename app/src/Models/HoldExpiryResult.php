<?php

namespace App\Models;

final class HoldExpiryResult
{
    /**
     * @param int[] $expiredAttemptIds
     */
    public function __construct(
        private int $releasedCount,
        private array $expiredAttemptIds,
        private bool $ran = true,
        private ?string $reason = null,
    ) {
    }

    public static function executed(int $releasedCount, array $expiredAttemptIds): self
    {
        return new self($releasedCount, $expiredAttemptIds, true, null);
    }

    public static function skipped(string $reason): self
    {
        return new self(0, [], false, $reason);
    }

    public function releasedCount(): int
    {
        return $this->releasedCount;
    }

    public function getReleasedCount(): int
    {
        return $this->releasedCount;
    }

    /**
     * @return int[]
     */
    public function expiredAttemptIds(): array
    {
        return $this->expiredAttemptIds;
    }

    /**
     * @return int[]
     */
    public function getExpiredAttemptIds(): array
    {
        return $this->expiredAttemptIds;
    }

    public function ran(): bool
    {
        return $this->ran;
    }

    public function wasExecuted(): bool
    {
        return $this->ran;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function getSkipReason(): ?string
    {
        return $this->reason;
    }

    public function toArray(): array
    {
        return [
            'released_count' => $this->releasedCount,
            'expired_attempt_ids' => $this->expiredAttemptIds,
            'ran' => $this->ran,
            'reason' => $this->reason,
        ];
    }
}
