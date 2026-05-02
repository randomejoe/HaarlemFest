<?php

namespace App\Repositories\Interfaces;

interface ICheckoutRepository
{
    public function findEventForUpdate(int $eventId): ?array;

    public function decrementStock(int $eventId, int $quantity): void;

    public function createInvoice(int $userId, float $totalPrice): int;

    public function createTicket(int $invoiceId, int $userId, int $eventId, float $price): void;

    public function findInvoiceById(int $invoiceId): ?array;

    public function findTicketsByInvoiceId(int $invoiceId): array;
}
