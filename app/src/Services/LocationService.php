<?php

namespace App\Services;

use App\Repositories\LocationRepository;
use App\Models\Location;
use App\Services\ImageUploader;

class LocationService implements CMSServiceInterface
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
    public function updateWithImage(int $id, array $postData, array $fileData): bool
    {
        $data = $postData;
        $data['location_id'] = $id;
        $image = $_FILES['image'];

        $data['image'] = ImageUploader::upload($image);

        $location = Location::fromArray($data);
        return $this->repository->updateLocation($location);
    }

    public function update(int $id, array $postData)
    {
        $postData['location_id'] = $id;

        $location = Location::fromArray($postData);
        return $this->repository->updateLocation($location);
    }
    public function delete(int $id): bool {
        return $this->repository->deleteLocation($id);
    }

    public function getAll(): array {
        $locations = $this->repository->getAllLocations();
        return $locations;
    }

    public function create(array $postData) {
        return $this->repository->createLocation($postData);
    }

    private function dataToLocationModel(array $data): Location {
        return Location::fromArray($data);
    }
}
