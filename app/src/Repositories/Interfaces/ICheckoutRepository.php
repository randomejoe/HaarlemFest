<?php

namespace App\Repositories\Interfaces;

interface ICheckoutRepository
{
    public function findById(int $checkoutAttemptId): ?array;

    public function findByIdForUpdate(int $checkoutAttemptId): ?array;

    public function findByIdempotencyKey(string $idempotencyKey): ?array;

    public function createAttempt(array $data): int;

    public function createAttemptItems(int $checkoutAttemptId, array $items): void;

    public function markHandoffCreated(int $checkoutAttemptId, string $provider, string $reference): void;

    public function markHandoffFailed(int $checkoutAttemptId, string $errorCode, string $errorMessage): void;

    public function markPaid(int $checkoutAttemptId): void;

    public function markExpiredByIds(array $attemptIds): void;

    public function findItemsWithEventData(int $checkoutAttemptId): array;

    public function findItemsByAttemptId(int $checkoutAttemptId): array;

    public function createInvoice(int $userId, float $totalPrice): int;

    public function createTicketsForAttempt(int $checkoutAttemptId, int $userId, int $invoiceId): array;

    public function findInvoiceById(int $invoiceId): ?array;

    public function findTicketsByInvoiceId(int $invoiceId): array;
}
