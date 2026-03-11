<?php

namespace App\Repositories;

use App\Models\Event;
use PDO;
use RuntimeException;

class EventRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return Event[]
     */
    public function findByCategory(string $category): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                e.event_id,
                e.name,
                e.location,
                e.start_time,
                e.end_time,
                e.ticket_price,
                e.ticket_amount,
                e.description,
                e.category,
                COALESCE(NULLIF(e.location, \"\"), v.location) AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE LOWER(COALESCE(e.category, \"\")) = LOWER(:category)
            ORDER BY e.start_time ASC, e.name ASC'
        );

        $stmt->execute(['category' => $category]);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(fn (array $row): Event => $this->hydrateEvent($row), $rows);
    }

    public function findById(int $eventId): ?Event
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                e.event_id,
                e.name,
                e.location,
                e.start_time,
                e.end_time,
                e.ticket_price,
                e.ticket_amount,
                e.description,
                e.category,
                COALESCE(NULLIF(e.location, \"\"), v.location) AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE e.event_id = :event_id
            LIMIT 1'
        );

        $stmt->execute(['event_id' => $eventId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrateEvent($row) : null;
    }

    /**
     * @return array<int, Event>
     */
    public function findByIds(array $eventIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $eventIds)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT
                e.event_id,
                e.name,
                e.location,
                e.start_time,
                e.end_time,
                e.ticket_price,
                e.ticket_amount,
                e.description,
                e.category,
                COALESCE(NULLIF(e.location, \"\"), v.location) AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE e.event_id IN (' . $placeholders . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);

        $rows = $stmt->fetchAll() ?: [];
        $byId = [];

        foreach ($rows as $row) {
            $event = $this->hydrateEvent($row);
            $byId[$event->id()] = $event;
        }

        return $byId;
    }

    public function findStockByIds(array $eventIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $eventIds)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT event_id, name, COALESCE(ticket_amount, 0) AS ticket_amount
            FROM events
            WHERE event_id IN (' . $placeholders . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll() ?: [];

        $stock = [];
        foreach ($rows as $row) {
            $stock[(int) $row['event_id']] = [
                'event_id' => (int) $row['event_id'],
                'name' => (string) $row['name'],
                'ticket_amount' => max(0, (int) $row['ticket_amount']),
            ];
        }

        return $stock;
    }

    public function decrementTicketAmountIfAvailable(int $eventId, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE events
             SET ticket_amount = ticket_amount - :quantity
             WHERE event_id = :event_id
               AND ticket_amount IS NOT NULL
               AND ticket_amount >= :quantity'
        );

        $stmt->execute([
            'event_id' => $eventId,
            'quantity' => $quantity,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function incrementTicketAmount(int $eventId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE events
             SET ticket_amount = COALESCE(ticket_amount, 0) + :quantity
             WHERE event_id = :event_id'
        );

        $stmt->execute([
            'event_id' => $eventId,
            'quantity' => $quantity,
        ]);
    }

    private function hydrateEvent(array $row): Event
    {
        return new Event(
            (int) ($row['event_id'] ?? 0),
            (string) ($row['name'] ?? ''),
            isset($row['location']) ? (string) $row['location'] : null,
            (string) ($row['start_time'] ?? ''),
            (string) ($row['end_time'] ?? ''),
            isset($row['ticket_price']) ? (float) $row['ticket_price'] : 0.0,
            isset($row['ticket_amount']) ? ($row['ticket_amount'] !== null ? (int) $row['ticket_amount'] : null) : null,
            (string) ($row['description'] ?? ''),
            isset($row['category']) && $row['category'] !== null ? (string) $row['category'] : null,
            (string) ($row['venue_location'] ?? 'Venue to be announced'),
        );
    }
}
