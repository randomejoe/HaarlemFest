<?php

namespace App\Repositories;

use PDO;
use App\Models\Location;

class LocationRepository extends BaseRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function updateLocation(Location $location) {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare("UPDATE locations SET name = :name WHERE location_id = :id");
        $stmt->execute([
            'id' => $location->id,
            'name' => $location->name,
        ]);
        return true;
    }
    public function getAllLocations(): array 
    {
        $stmt = $this->pdo->prepare('SELECT name as item_name, location_id AS item_id, description, image  FROM locations');
        $stmt->execute();
        $locations = $stmt->fetchAll();
        return $locations;
    }
    public function createPage(string $title, int $isMainEvent): bool 
    {
        $this->requireAdmin();
        $stmt = $this->pdo->prepare('INSERT INTO pages (title, is_main_event) VALUES (:title, :mainEvent)');
        $stmt->execute(['title' => $title,
        'mainEvent' => $isMainEvent]);
        return true;
    }
    public function getPageForEdit(int $id)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare(
            "SELECT p.title as item_name, pc.content_id, pc.component_name, pc.data
            FROM pages p
            LEFT JOIN page_content pc ON p.page_id = pc.page_id
            WHERE p.page_id = :page_id"
            );
        $stmt->execute(['page_id' => $id]);
        $page = $stmt->fetchAll();
        return $page;
    }
}
