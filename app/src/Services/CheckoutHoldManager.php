<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CheckoutRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketHoldRepository;
use App\Services\Results\HoldExpiryResult;
use PDO;

final class CheckoutHoldManager
{
    private const EXPIRY_CLEANUP_COOLDOWN_SECONDS = 60;
    private const HOLD_DURATION_SECONDS = 600;

    public function __construct(
        private TicketHoldRepository $ticketHolds,
        private CheckoutRepository $checkoutAttempts,
        private EventRepository $events,
        private DateTimeFormatter $dateTimeFormatter,
        private HoldExpiryEvaluator $expiryEvaluator,
        private ExpiryCleanupLogger $logger,
        private PDO $pdo,
    ) {}

    public function createHoldsForAttempt(
        int $attemptId,
        int $userId,
        string $plannerToken,
        array $attemptItems,
        string $expiresAt
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
            $releaseCutoff = $this->dateTimeFormatter->addSeconds(
                $this->dateTimeFormatter->currentTimestamp(),
                -self::EXPIRY_GRACE_PERIOD_SECONDS
            );
            $releasedAt = $this->dateTimeFormatter->currentDateTime();
            $releasedCount = 0;
            $expiredAttemptIds = [];

            $expiredHolds = $this->ticketHolds->findExpiredHoldsForUpdate($releaseCutoff);
            if ($expiredHolds === []) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }

                return new HoldExpiryResult(0, []);
            }

            $holdIds = [];
            foreach ($expiredHolds as $hold) {
                $holdIds[] = (int) $hold['ticket_hold_id'];
                $expiredAttemptIds[] = (int) $hold['checkout_attempt_id'];
                $this->events->incrementTicketAmount((int) $hold['event_id'], (int) $hold['quantity']);
                $releasedCount++;
            }

            $this->ticketHolds->markReleasedByIds($holdIds, 'expired', $releasedAt);
            $this->checkoutAttempts->markExpiredByIds($expiredAttemptIds);

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return new HoldExpiryResult(
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

    public function releaseExpiredHoldsIfNeeded(PlannerService $planner, bool $force = false): HoldExpiryResult
    {
        if (!$force && !$planner->shouldRunExpiryCleanup(self::EXPIRY_CLEANUP_COOLDOWN_SECONDS)) {
            $this->logger->logSkipped($force, 'cooldown', self::EXPIRY_CLEANUP_COOLDOWN_SECONDS);
            return new HoldExpiryResult(0, [], false, 'cooldown');
        }

        $result = $this->releaseExpiredHolds();
        $planner->markExpiryCleanupRun();

        $this->logger->logExecuted(
            $result->getReleasedCount(),
            count($result->getExpiredAttemptIds()),
            $force
        );

        return $result;
    }

    public function isHoldPastGracePeriod(string $holdExpiresAt): bool
    {
        return $this->expiryEvaluator->isPastGracePeriod($holdExpiresAt);
    }

    public function markHoldsAsTransferred(int $attemptId): void
    {
        $this->ticketHolds->markTransferredByAttemptId($attemptId);
    }

    public function markHoldsAsPaid(int $attemptId, ?string $paidAt = null): void
    {
        $this->ticketHolds->markPaidByAttemptId(
            $attemptId,
            $paidAt ?? $this->dateTimeFormatter->currentDateTime()
        );
    }
}
