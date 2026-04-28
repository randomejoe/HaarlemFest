<?php

namespace App\Repositories\Interfaces;

interface ITicketHoldRepository
{
    public function createHolds(int $checkoutAttemptId, ?int $userId, string $plannerToken, array $items, string $expiresAt): void;

    public function findExpiredHoldsForUpdate(string $now): array;

    public function findByAttemptForUpdate(int $checkoutAttemptId): array;

    public function markReleasedByIds(array $ticketHoldIds, string $reason, string $releasedAt): void;

    public function markTransferredByAttemptId(int $checkoutAttemptId): void;

    public function markPaidByAttemptId(int $checkoutAttemptId, string $releasedAt): void;
}
