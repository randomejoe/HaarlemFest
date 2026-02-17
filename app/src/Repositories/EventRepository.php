<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class EventRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
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
}
