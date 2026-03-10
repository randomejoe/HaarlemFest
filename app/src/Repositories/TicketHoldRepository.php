<?php

namespace App\Repositories;

use PDO;

class TicketHoldRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createHolds(int $checkoutAttemptId, ?int $userId, string $plannerToken, array $items, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ticket_holds (
                checkout_attempt_id,
                event_id,
                user_id,
                planner_token,
                quantity,
                status,
                expires_at,
                release_reason,
                created_at,
                released_at
            ) VALUES (
                :checkout_attempt_id,
                :event_id,
                :user_id,
                :planner_token,
                :quantity,
                :status,
                :expires_at,
                NULL,
                NOW(),
                NULL
            )'
        );

        foreach ($items as $item) {
            $stmt->execute([
                'checkout_attempt_id' => $checkoutAttemptId,
                'event_id' => $item['event_id'],
                'user_id' => $userId,
                'planner_token' => $plannerToken,
                'quantity' => $item['quantity'],
                'status' => 'active',
                'expires_at' => $expiresAt,
            ]);
        }
    }

    public function findExpiredHoldsForUpdate(string $now): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                ticket_hold_id,
                checkout_attempt_id,
                event_id,
                quantity,
                status,
                expires_at
            FROM ticket_holds
            WHERE expires_at <= :now
              AND status IN (\'active\', \'transferred\')
            FOR UPDATE'
        );

        $stmt->execute([
            'now' => $now,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    public function findByAttemptForUpdate(int $checkoutAttemptId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                ticket_hold_id,
                checkout_attempt_id,
                event_id,
                quantity,
                status,
                expires_at
            FROM ticket_holds
            WHERE checkout_attempt_id = :checkout_attempt_id
              AND status IN (\'active\', \'transferred\')
            FOR UPDATE'
        );

        $stmt->execute([
            'checkout_attempt_id' => $checkoutAttemptId,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    public function markReleasedByIds(array $ticketHoldIds, string $reason, string $releasedAt): void
    {
        $ids = array_values(array_unique(array_map('intval', $ticketHoldIds)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE ticket_holds
            SET status = ?,
                release_reason = ?,
                released_at = ?
            WHERE ticket_hold_id IN (' . $placeholders . ')
              AND status IN (?, ?)';

        $params = array_merge(['released', $reason, $releasedAt], $ids, ['active', 'transferred']);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function markTransferredByAttemptId(int $checkoutAttemptId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ticket_holds
             SET status = :status
             WHERE checkout_attempt_id = :checkout_attempt_id
               AND status = :from_status'
        );

        $stmt->execute([
            'status' => 'transferred',
            'checkout_attempt_id' => $checkoutAttemptId,
            'from_status' => 'active',
        ]);
    }

    public function markPaidByAttemptId(int $checkoutAttemptId, string $releasedAt): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ticket_holds
             SET status = :status,
                 release_reason = :release_reason,
                 released_at = :released_at
             WHERE checkout_attempt_id = :checkout_attempt_id
               AND status IN (\'active\', \'transferred\')'
        );

        $stmt->execute([
            'status' => 'released',
            'release_reason' => 'paid',
            'released_at' => $releasedAt,
            'checkout_attempt_id' => $checkoutAttemptId,
        ]);
    }
}
