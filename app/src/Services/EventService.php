<?php

namespace App\Services;

use App\Repositories\EventRepository;

class EventService implements CMSService
{
    private EventRepository $repository;

    public function __construct(EventRepository $repository)
    {
        $this->repository = $repository;
    }
    public function getForEdit(int $id)
    {
        return $this->repository->getEventForEdit($id);
    }
    public function isNameEditable(): bool {
        return true;
    }
    public function getPageId(int $id) {
        return $this->repository->getContentPageId($id)['page_id'];
    }
    public function update(int $id, array $postData): bool
    {
        return $this->repository->updateEvent($id, $postData);
    }
    public function delete(int $id): bool {
        return $this->repository->deleteEvent($id);
    }

    public function getAll() {
        return $this->repository->getAllEvents();
    }
    public function getAllInCategory(string $category) {
        return $this->repository->getAllEventsInCategory($category);
    }
}
