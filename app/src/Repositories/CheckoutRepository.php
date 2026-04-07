<?php

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

class CheckoutRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $checkoutAttemptId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkout_attempts WHERE checkout_attempt_id = :id LIMIT 1');
        $stmt->execute(['id' => $checkoutAttemptId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByIdForUpdate(int $checkoutAttemptId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM checkout_attempts
             WHERE checkout_attempt_id = :id
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute(['id' => $checkoutAttemptId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkout_attempts WHERE idempotency_key = :key LIMIT 1');
        $stmt->execute(['key' => $idempotencyKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createAttempt(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO checkout_attempts (
                user_id,
                planner_token,
                status,
                total_price,
                currency,
                hold_expires_at,
                idempotency_key,
                payment_provider,
                payment_reference,
                error_code,
                error_message,
                created_at,
                updated_at
            ) VALUES (
                :user_id,
                :planner_token,
                :status,
                :total_price,
                :currency,
                :hold_expires_at,
                :idempotency_key,
                :payment_provider,
                :payment_reference,
                :error_code,
                :error_message,
                NOW(),
                NOW()
            )'
        );

        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'planner_token' => $data['planner_token'],
            'status' => $data['status'],
            'total_price' => $data['total_price'],
            'currency' => $data['currency'] ?? 'EUR',
            'hold_expires_at' => $data['hold_expires_at'],
            'idempotency_key' => $data['idempotency_key'],
            'payment_provider' => $data['payment_provider'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createAttemptItems(int $checkoutAttemptId, array $items): void
    {
        if ($items === []) {
            return;
        }

        $valuesSql = [];
        $params = [];

        foreach ($items as $item) {
            foreach (['event_id', 'quantity', 'unit_price', 'line_total'] as $requiredKey) {
                if (!array_key_exists($requiredKey, $item)) {
                    throw new InvalidArgumentException('Missing required checkout attempt item field: ' . $requiredKey);
                }
            }

            $valuesSql[] = '(?, ?, ?, ?, ?, ?)';
            $params[] = $checkoutAttemptId;
            $params[] = (int) $item['event_id'];
            $params[] = (int) $item['quantity'];
            $params[] = (float) $item['unit_price'];
            $params[] = (float) $item['line_total'];
            $params[] = (bool) $item['family_ticket'];
        }

        $sql = 'INSERT INTO checkout_attempt_items (
                checkout_attempt_id,
                event_id,
                quantity,
                unit_price,
                line_total, 
                family_ticket
            ) VALUES ' . implode(', ', $valuesSql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function markHandoffCreated(int $checkoutAttemptId, string $provider, string $reference): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE checkout_attempts
             SET status = :status,
                 payment_provider = :provider,
                 payment_reference = :reference,
                 error_code = NULL,
                 error_message = NULL,
                 updated_at = NOW()
             WHERE checkout_attempt_id = :id'
        );

        $stmt->execute([
            'status' => 'handoff_created',
            'provider' => $provider,
            'reference' => $reference,
            'id' => $checkoutAttemptId,
        ]);
    }

    public function markHandoffFailed(int $checkoutAttemptId, string $errorCode, string $errorMessage): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE checkout_attempts
             SET status = :status,
                 error_code = :error_code,
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE checkout_attempt_id = :id'
        );

        $stmt->execute([
            'status' => 'handoff_failed',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'id' => $checkoutAttemptId,
        ]);
    }

    public function markPaid(int $checkoutAttemptId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE checkout_attempts
             SET status = :status,
                 error_code = NULL,
                 error_message = NULL,
                 updated_at = NOW()
             WHERE checkout_attempt_id = :id'
        );

        $stmt->execute([
            'status' => 'paid',
            'id' => $checkoutAttemptId,
        ]);
    }

    public function markExpiredByIds(array $attemptIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $attemptIds)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE checkout_attempts
            SET status = ?,
                updated_at = NOW()
            WHERE checkout_attempt_id IN (' . $placeholders . ')
              AND status IN (?, ?)';

        $params = array_merge(['expired'], $ids, ['initiated', 'handoff_created']);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function findItemsWithEventData(int $checkoutAttemptId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                i.checkout_attempt_item_id,
                i.checkout_attempt_id,
                i.event_id,
                i.quantity,
                i.unit_price,
                i.line_total,
                e.name,
                e.start_time,
                e.end_time,
                COALESCE(NULLIF(e.location, \'\'), v.location) AS venue_location
            FROM checkout_attempt_items i
            LEFT JOIN events e ON e.event_id = i.event_id
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE i.checkout_attempt_id = :checkout_attempt_id
            ORDER BY i.checkout_attempt_item_id ASC'
        );

        $stmt->execute(['checkout_attempt_id' => $checkoutAttemptId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findItemsByAttemptId(int $checkoutAttemptId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                event_id,
                quantity,
                unit_price,
                line_total,
                family_ticket
            FROM checkout_attempt_items
            WHERE checkout_attempt_id = :checkout_attempt_id
            ORDER BY checkout_attempt_item_id ASC'
        );

        $stmt->execute(['checkout_attempt_id' => $checkoutAttemptId]);
        return $stmt->fetchAll() ?: [];
    }

    public function createInvoice(int $userId, float $totalPrice): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO invoices (
                user_id,
                total_price,
                created_at
            ) VALUES (
                :user_id,
                :total_price,
                NOW()
            )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'total_price' => $totalPrice,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createTicketsForAttempt(int $checkoutAttemptId, int $userId, int $invoiceId): array
    {
        $items = $this->findItemsByAttemptId($checkoutAttemptId);
        if ($items === []) {
            return [];
        }

        $valuesSql = [];
        $insertParams = [];
        $ticketsToCreate = [];

        foreach ($items as $item) {
            $eventId = (int) ($item['event_id'] ?? 0);
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            $ticketPrice = (float) ($item['unit_price'] ?? 0.0);
            $familyTicket = (bool) ($item['family_ticket'] ?? false);

            if ($eventId <= 0 || $quantity <= 0) {
                continue;
            }

            for ($i = 0; $i < $quantity; $i++) {
                $verificationCode = $this->generateVerificationCode($checkoutAttemptId, $eventId);

                $valuesSql[] = '(?, ?, ?, ?, ?, ?)';
                $insertParams[] = $eventId;
                $insertParams[] = $userId;
                $insertParams[] = $invoiceId;
                $insertParams[] = $ticketPrice;
                $insertParams[] = $verificationCode;
                $insertParams[] = $familyTicket;

                $ticketsToCreate[] = [
                    'event_id' => $eventId,
                    'ticket_price' => $ticketPrice,
                    'verification_code' => $verificationCode,
                ];
            }
        }

        if ($ticketsToCreate === []) {
            return [];
        }

        $sql = 'INSERT INTO tickets (
                event_id,
                user_id,
                invoice_id,
                ticket_price,
                verification_code,
                family_ticket
            ) VALUES ' . implode(', ', $valuesSql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($insertParams);

        return $this->findCreatedTickets($invoiceId, $ticketsToCreate);
    }

    private function generateVerificationCode(int $checkoutAttemptId, int $eventId): string
    {
        return strtoupper(
            substr(hash('sha256', $checkoutAttemptId . '|' . $eventId . '|' . bin2hex(random_bytes(10))), 0, 20)
        );
    }

    private function findCreatedTickets(int $invoiceId, array $ticketsToCreate): array
    {
        if ($ticketsToCreate === []) {
            return [];
        }

        $verificationCodes = array_map(
            static fn (array $ticket): string => (string) ($ticket['verification_code'] ?? ''),
            $ticketsToCreate
        );

        $placeholders = implode(',', array_fill(0, count($verificationCodes), '?'));
        $params = array_merge([$invoiceId], $verificationCodes);

        $stmt = $this->pdo->prepare(
            'SELECT
                ticket_id,
                event_id,
                ticket_price,
                verification_code,
                family_ticket
            FROM tickets
            WHERE invoice_id = ?
              AND verification_code IN (' . $placeholders . ')'
        );
        $stmt->execute($params);

        $rows = $stmt->fetchAll() ?: [];
        $ticketsByCode = [];

        foreach ($rows as $row) {
            $ticketsByCode[(string) ($row['verification_code'] ?? '')] = [
                'ticket_id' => (int) ($row['ticket_id'] ?? 0),
                'event_id' => (int) ($row['event_id'] ?? 0),
                'ticket_price' => (float) ($row['ticket_price'] ?? 0),
                'verification_code' => (string) ($row['verification_code'] ?? ''),
            ];
        }

        $createdTickets = [];
        foreach ($ticketsToCreate as $ticket) {
            $verificationCode = (string) ($ticket['verification_code'] ?? '');
            if (!isset($ticketsByCode[$verificationCode])) {
                throw new InvalidArgumentException('Failed to load inserted ticket: ' . $verificationCode);
            }

            $createdTickets[] = $ticketsByCode[$verificationCode];
        }

        return $createdTickets;
    }
}
