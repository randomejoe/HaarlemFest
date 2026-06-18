<?php

namespace App\Repositories;

use App\Repositories\Interfaces\ICheckoutRepository;
use PDO;

class CheckoutRepository implements ICheckoutRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findEventForUpdate(int $eventId): ?array
    {
        // FOR UPDATE locks the row so two buyers cannot both pass the stock check.
        $stmt = $this->pdo->prepare(
            'SELECT event_id, name, ticket_amount AS available_tickets, ticket_price
             FROM events
             WHERE event_id = :id
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute(['id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function decrementStock(int $eventId, int $quantity): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE events
             SET ticket_amount = ticket_amount - :quantity
             WHERE event_id = :id'
        );
        $stmt->execute([
            'quantity' => $quantity,
            'id' => $eventId,
        ]);
    }

    public function createInvoice(int $userId, float $totalPrice): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO invoices (user_id, total_price, created_at)
             VALUES (:user_id, :total_price, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'total_price' => $totalPrice,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createTicket(int $invoiceId, int $userId, int $eventId, float $price): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tickets (event_id, user_id, invoice_id, ticket_price, verification_code)
             VALUES (:event_id, :user_id, :invoice_id, :price, :code)'
        );
        $stmt->execute([
            'event_id' => $eventId,
            'user_id' => $userId,
            'invoice_id' => $invoiceId,
            'price' => $price,
            'code' => strtoupper(bin2hex(random_bytes(6))),
        ]);
    }

    public function findInvoiceById(int $invoiceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                invoice_id,
                user_id,
                total_price,
                created_at AS issued_at,
                CONCAT(\'INV-\', invoice_id) AS invoice_number
             FROM invoices
             WHERE invoice_id = :invoice_id
             LIMIT 1'
        );
        $stmt->execute(['invoice_id' => $invoiceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['currency'] = 'EUR';

        return $row;
    }

    public function findTicketsByInvoiceId(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                t.ticket_id,
                t.event_id,
                t.user_id,
                t.invoice_id,
                t.ticket_price,
                t.verification_code,
                t.family_ticket,
                e.name AS event_name,
                DATE_FORMAT(e.start_time, \'%Y-%m-%d\') AS event_date,
                DATE_FORMAT(e.start_time, \'%H:%i\') AS event_time,
                COALESCE(NULLIF(e.location, \'\'), v.location) AS venue
             FROM tickets t
             LEFT JOIN events e ON e.event_id = t.event_id
             LEFT JOIN venues v ON v.venue_id = e.venue_id
             WHERE t.invoice_id = :invoice_id
             ORDER BY t.ticket_id ASC'
        );
        $stmt->execute(['invoice_id' => $invoiceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
