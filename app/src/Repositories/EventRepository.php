<?php

namespace App\Repositories;

use PDO;
use RuntimeException;

class EventRepository extends BaseRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

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
                COALESCE(NULLIF(e.location, \'\'), v.location) AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE LOWER(COALESCE(e.category, \'\')) = LOWER(:category)
            ORDER BY e.start_time ASC, e.name ASC'
        );

        $stmt->execute(['category' => $category]);
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $eventId): ?array
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
                COALESCE(NULLIF(e.location, \'\'), v.location) AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE e.event_id = :event_id
            LIMIT 1'
        );

        $stmt->execute(['event_id' => $eventId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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
                COALESCE(NULLIF(e.location, \'\'), v.location) AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE e.event_id IN (' . $placeholders . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);

        $rows = $stmt->fetchAll() ?: [];
        $byId = [];

        foreach ($rows as $row) {
            $byId[(int) $row['event_id']] = $row;
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
    public function getAllEvents() 
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.name as item_name, e.event_id AS item_id, 
            e.location, e.ticket_amount, e.ticket_price, e.category, COUNT(t.ticket_id) AS sold_tickets 
            FROM events e
            LEFT JOIN tickets t ON t.event_id = e.event_id
            GROUP BY e.event_id'
            );
        $stmt->execute();
        $events = $stmt->fetchAll();
        return $events;
    }
    public function getAllEventsInCategory(string $category) 
    {
        $stmt = $this->pdo->prepare('SELECT e.name as item_name, e.event_id AS item_id, 
            e.location, e.ticket_amount, e.ticket_price, e.category, COUNT(t.ticket_id) AS sold_tickets 
            FROM events e
            LEFT JOIN tickets t ON t.event_id = e.event_id
            WHERE e.category = :category
            GROUP BY e.event_id');
        $stmt->execute(['category'=> $category]);
        $events = $stmt->fetchAll();
        return $events;
    }

    public function createSubEvent(string $category, array $postData) {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare('INSERT INTO events (name, location, start_time, end_time, ticket_price, ticket_amount, description, language, category) 
        VALUES (:name, :location, :start_time, :end_time, :price, :amount, :description, :language, :category)');

        $stmt->execute(['name' => $postData['item_name'], 
        'location' => $postData['location'], 
        'start_time' => $postData['start_time'], 
        'end_time' => $postData['end_time'], 
        'price' => $postData['ticket_price'], 
        'amount' => $postData['ticket_amount'], 
        'description' => $postData['description'], 
        'language' => $postData['language'],
        'category' => $category]);
        return true;
    }

    public function getEventForEdit(int $id) {
        $stmt = $this->pdo->prepare(
            'SELECT name AS item_name, event_id AS item_id, location, ticket_amount, ticket_price, category, start_time, end_time, description, language
            FROM events
            WHERE event_id = :id'
            );
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch();
        return $event;
    }

    public function updateEvent(int $id, array $postData) {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare('UPDATE events SET name = :name, location = :location, start_time = :start_time, end_time = :end_time, ticket_price = :price, ticket_amount = :amount, description = :description, language = :language, category = :category');

        $stmt->execute(['name' => $postData['name'], 
        'location' => $postData['location'], 
        'start_time' => $postData['start_time'], 
        'end_time' => $postData['end_time'], 
        'price' => $postData['ticket_price'], 
        'amount' => $postData['ticket_amount'], 
        'description' => $postData['description'], 
        'language' => $postData['language'],
        'category' => $postData['category']]);
        return true;
    }
}
