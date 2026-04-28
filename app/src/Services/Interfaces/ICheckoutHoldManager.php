<?php

namespace App\Services\Interfaces;

use App\Models\HoldExpiryResult;
use PDO;

interface ICheckoutHoldManager
{
    public function createHoldsForAttempt(
        int $attemptId,
        int $userId,
        string $plannerToken,
        array $attemptItems,
        string $expiresAt,
        PDO $pdo
    ): void;

    public function releaseExpiredHolds(): HoldExpiryResult;

    public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult;

    public function markHoldsAsTransferred(int $attemptId, PDO $pdo): void;

    public function markHoldsAsPaid(int $attemptId, ?string $paidAt, PDO $pdo): void;

    public function isHoldPastGracePeriod(string $holdExpiresAt): bool;
}
