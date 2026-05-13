<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\HistoryTourLanguage;
use App\Repositories\Interfaces\IEventRepository;
use PDO;
use RuntimeException;

class EventRepository implements IEventRepository
{
    private PDO $pdo;

    private const BASE_EVENT_SELECT = "SELECT
                e.event_id,
                e.name,
                e.location,
                e.start_time,
                e.end_time,
                e.ticket_price,
                e.ticket_amount,
                e.description,
                e.category,
                e.language,
                e.artist_img,
                COALESCE(NULLIF(v.location, ''), NULLIF(e.location, ''), 'Venue to be announced') AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id";

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
            self::BASE_EVENT_SELECT . '
            WHERE LOWER(COALESCE(e.category, \'\')) = LOWER(:category)
            ORDER BY e.start_time ASC, e.name ASC'
        );

        $stmt->execute(['category' => $category]);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(fn(array $row): Event => $this->hydrateEvent($row), $rows);
    }

    public function findById(int $eventId): ?Event
    {
        $stmt = $this->pdo->prepare(
            self::BASE_EVENT_SELECT . '
            WHERE e.event_id = :event_id
            LIMIT 1'
        );

        $stmt->execute(['event_id' => $eventId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrateEvent($row) : null;
    }

    /**
     * @return Event[]
     */
    public function findByName(string $eventName): array
    {
        $stmt = $this->pdo->prepare(
            self::BASE_EVENT_SELECT . '
            WHERE LOWER(TRIM(COALESCE(e.name, \'\'))) = LOWER(TRIM(:event_name))
            ORDER BY e.start_time ASC, e.name ASC'
        );

        $stmt->execute(['event_name' => $eventName]);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(fn(array $row): Event => $this->hydrateEvent($row), $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findVenuesByArtist(string $artistName): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                e.event_id,
                e.name,
                e.location AS event_location,
                e.start_time,
                e.end_time,
                e.venue_id,
                v.location AS venue_location,
                v.capacity AS venue_capacity
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE LOWER(TRIM(COALESCE(e.name, ''))) = LOWER(TRIM(:event_name))
            ORDER BY e.start_time ASC, e.event_id ASC"
        );

        $stmt->execute(['event_name' => $artistName]);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, Event>
     */
    public function findByIds(array $eventIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $eventIds)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = self::BASE_EVENT_SELECT . '
            WHERE e.event_id IN (' . $placeholders . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);

        $rows = $stmt->fetchAll() ?: [];
        $byId = [];

        foreach ($rows as $row) {
            $event = $this->hydrateEvent($row);
            $byId[$event->getId()] = $event;
        }

        return $byId;
    }

    public function findStockByIds(array $eventIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $eventIds)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

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
            isset($row['sold_tickets']) ? (int) $row['sold_tickets'] : null,
            isset($row['language']) && $row['language'] !== null ? HistoryTourLanguage::convertToLanguage((string) $row['language']) : null,
            isset($row['artist_img']) && $row['artist_img'] !== null ? (string) $row['artist_img'] : null,
        );
    }

    public function getAllEvents(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.name, e.event_id,
            e.location, e.ticket_amount, e.ticket_price, e.category, e.language, e.description, e.artist_img, COUNT(t.ticket_id) AS sold_tickets, e.start_time, e.end_time
            FROM events e
            LEFT JOIN tickets t ON t.event_id = e.event_id
            GROUP BY e.event_id'
        );
        $stmt->execute();
        $events = $stmt->fetchAll();
        $returnEvents = [];
        foreach ($events as $event) {
            $returnEvents[] = $this->hydrateEvent($event);
        }
        return $returnEvents;
    }

    public function getAllEventsInCategory(string $category): array
    {
        $stmt = $this->pdo->prepare("SELECT e.name, e.event_id,
            e.location, e.ticket_amount, e.ticket_price, e.category, COUNT(t.ticket_id) AS sold_tickets, e.language, e.description, e.artist_img, e.start_time, e.end_time,
            COALESCE(NULLIF(v.location, ''), NULLIF(e.location, ''), 'Venue to be announced') AS venue_location
            FROM events e
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            LEFT JOIN tickets t ON t.event_id = e.event_id
            WHERE e.category = :category
            GROUP BY e.event_id");
        $stmt->execute(['category' => $category]);
        $events = $stmt->fetchAll();

        $returnEvents = [];
        foreach ($events as $event) {
            $returnEvents[] = $this->hydrateEvent($event);
        }
        return $returnEvents;
    }

    public function createSubEvent(string $category, array $postData)
    {
        $stmt = $this->pdo->prepare('INSERT INTO events (name, location, start_time, end_time, ticket_price, ticket_amount, description, language, category)
        VALUES (:name, :location, :start_time, :end_time, :price, :amount, :description, :language, :category)');

        $ticketAmount = $postData['ticket_price'] > 0 ? $postData['ticket_amount'] : null;
        $stmt->execute([
            'name' => $postData['item_name'],
            'location' => $postData['location'],
            'start_time' => $postData['start_time'],
            'end_time' => $postData['end_time'],
            'price' => $postData['ticket_price'],
            'amount' => $ticketAmount,
            'description' => $postData['description'],
            'language' => $postData['language'],
            'category' => $category
        ]);
        return true;
    }

    public function getEventForEdit(int $id): ?Event
    {
        $stmt = $this->pdo->prepare(
            'SELECT name, event_id, location, ticket_amount, ticket_price, category, start_time, end_time, description, language, artist_img
            FROM events
            WHERE event_id = :id'
        );
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch();

        return $event ? Event::fromArray($event) : null;
    }

    public function updateEvent(int $id, array $postData)
    {
        $stmt = $this->pdo->prepare('UPDATE events SET name = :name, location = :location, start_time = :start_time, end_time = :end_time, ticket_price = :price, ticket_amount = :amount, description = :description, language = :language, category = :category, artist_img = :artist_img WHERE event_id = :id');

        $stmt->execute([
            'name' => $postData['name'],
            'location' => $postData['location'],
            'start_time' => $postData['start_time'],
            'end_time' => $postData['end_time'],
            'price' => $postData['ticket_price'],
            'amount' => $postData['ticket_amount'],
            'description' => $postData['description'],
            'language' => $postData['language'],
            'category' => $postData['category'],
            'artist_img' => $postData['artist_img'] ?? null,
            'id' => $id
        ]);
        return true;
    }

    public function deleteEvent(int $id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM events WHERE event_id = :event_id");
        $stmt->execute([
            'event_id' => $id
        ]);
        return true;
    }
}
