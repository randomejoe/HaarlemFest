<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HoldExpiryResult;
use App\Repositories\Interfaces\ICheckoutRepository;
use App\Repositories\Interfaces\IEventRepository;
use App\Repositories\Interfaces\ITicketHoldRepository;
use App\Services\Interfaces\ICheckoutHoldManager;
use PDO;

final class CheckoutHoldManager implements ICheckoutHoldManager
{
    private const HOLD_DURATION_SECONDS = 600;

    public function __construct(
        private ITicketHoldRepository $ticketHolds,
        private ICheckoutRepository $checkoutAttempts,
        private IEventRepository $events,
        private DateTimeFormatter $dateTimeFormatter,
        private PDO $pdo,
    ) {
    }

    public function createHoldsForAttempt(
        int $attemptId,
        int $userId,
        string $plannerToken,
        array $attemptItems,
        string $expiresAt,
        PDO $pdo
    ): void {
        $this->ticketHolds->createHolds(
            $attemptId,
            $userId,
            $plannerToken,
            $attemptItems,
            $expiresAt
        );
    }

    public function releaseExpiredHolds(): HoldExpiryResult
    {
        $this->pdo->beginTransaction();

        try {
            $releaseCutoff = $this->dateTimeFormatter->currentDateTime();
            $releasedAt = $this->dateTimeFormatter->currentDateTime();
            $releasedCount = 0;
            $expiredAttemptIds = [];

            $expiredHolds = $this->ticketHolds->findExpiredHoldsForUpdate($releaseCutoff);
            if ($expiredHolds === []) {
                $this->pdo->commit();
                $this->logCleanup('executed', [
                    'released_count' => 0,
                    'expired_attempt_count' => 0,
                ]);

                return HoldExpiryResult::executed(0, []);
            }

            $holdIds = [];
            foreach ($expiredHolds as $hold) {
                $holdIds[] = (int) ($hold['ticket_hold_id'] ?? 0);
                $expiredAttemptIds[] = (int) ($hold['checkout_attempt_id'] ?? 0);
                $this->events->incrementTicketAmount((int) ($hold['event_id'] ?? 0), (int) ($hold['quantity'] ?? 0));
                $releasedCount++;
            }

            $this->ticketHolds->markReleasedByIds($holdIds, 'expired', $releasedAt);
            $this->checkoutAttempts->markExpiredByIds($expiredAttemptIds);

            $this->pdo->commit();
            $this->logCleanup('executed', [
                'released_count' => $releasedCount,
                'expired_attempt_count' => count(array_unique($expiredAttemptIds)),
            ]);

            return HoldExpiryResult::executed(
                $releasedCount,
                array_values(array_unique($expiredAttemptIds))
            );
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult
    {
        return $this->releaseExpiredHolds();
    }

    public function isHoldPastGracePeriod(string $holdExpiresAt): bool
    {
        if ($holdExpiresAt === '') {
            return false;
        }

        $expiryTimestamp = strtotime($holdExpiresAt);
        if ($expiryTimestamp === false) {
            return false;
        }

        return $expiryTimestamp <= $this->dateTimeFormatter->currentTimestamp();
    }

    public function markHoldsAsTransferred(int $attemptId, PDO $pdo): void
    {
        $this->ticketHolds->markTransferredByAttemptId($attemptId);
    }

    public function markHoldsAsPaid(int $attemptId, ?string $paidAt, PDO $pdo): void
    {
        $this->ticketHolds->markPaidByAttemptId(
            $attemptId,
            $paidAt ?? $this->dateTimeFormatter->currentDateTime()
        );
    }

    private function logCleanup(string $event, array $context): void
    {
        $parts = [];
        foreach ($context as $key => $value) {
            $parts[] = $key . '=' . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        }

        error_log('checkout_hold_cleanup ' . $event . ' ' . implode(' ', $parts));
    }
}
