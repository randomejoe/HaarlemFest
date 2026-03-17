<?php

namespace App\Services;

use App\Repositories\LocationRepository;
use App\Models\Location;

class LocationService implements CMSService
{
    private LocationRepository $repository;

    public function __construct(LocationRepository $repository)
    {
        $this->repository = $repository;
    }
    public function getForEdit(int $id)
    {
        return $this->repository->getLocationForEdit($id);
    }
    public function isNameEditable(): bool {
        return true;
    }
    public function update(int $id, array $postData): bool
    {
        return $this->repository->updateLocation($id, $postData);
    }
    public function delete(int $id): bool {
        return $this->repository->deleteLocation($id);
    }

    public function getAll(): array {
        $locations = $this->repository->getAllLocations();
        return $locations;
    }

    public function create(array $postData) {
        return $this->pageRepository->createLocation($title, 1);
    }

    private function dataToLocationModel(array $data): Location {
        return Location::fromArray($data);
    }
}
