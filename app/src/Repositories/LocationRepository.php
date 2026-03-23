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

        $stmt = $this->pdo->prepare("UPDATE locations SET name = :name, description = :description, image = :image WHERE location_id = :id");
        $stmt->execute([
            'id' => $location->getId(),
            'name' => $location->getName(),
            'description' => $location->getDescription(),
            'image' => $location->getImage(),
        ]);
        return true;
    }
    public function getAllLocations(): array 
    {
        $stmt = $this->pdo->prepare('SELECT name, location_id, description, image FROM locations');
        $stmt->execute();
        $locations = $stmt->fetchAll();
        $returnLocations = [];
        foreach ($locations as $location) {
            $returnLocations[] = Location::fromArray($location);
        }
        return $returnLocations;
    }
    public function createLocation(array $postData): bool 
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare('INSERT INTO locations (name) VALUES (:name)');
        $stmt->execute(['name' => $postData['item_name']]);
        return true;
    }
    public function getLocationForEdit(int $id)
    {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare(
            "SELECT name, location_id, description, image
            FROM locations
            WHERE location_id = :id"
            );
        $stmt->execute(['id' => $id]);
        $location = $stmt->fetch();

        return Location::fromArray($location);
    }
    public function deleteLocation(int $locationId) {
        $this->requireAdmin();

        $stmt = $this->pdo->prepare("DELETE FROM locations WHERE location_id = :location_id");
        $stmt->execute([
            'location_id' => $locationId
        ]);
        return true;
    }
}
