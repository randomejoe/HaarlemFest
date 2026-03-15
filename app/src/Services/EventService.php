<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Repositories\PageRepository;

class EventService implements CMSService
{
    private EventRepository $repository;
    private PageRepository $pageRepository;

    public function __construct(EventRepository $repository, PageRepository $pageRepository)
    {
        $this->repository = $repository;
        $this->pageRepository = $pageRepository;
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
    
    public function getCategories() {
        $categories = $this->pageRepository->getEventCategories();
        return array_column($categories, 'category');
    }

    public function create(array $postData) {
        // Main events need their own page and therefore are handled as pages with an extra tag
        $title = trim((string) ($postData['item_name'] ?? ''));
        return $this->pageRepository->createPage($title, 1);
    }
    public function createForCategory(string $category, array $postData) {
        return $this->repository->createSubEvent($postData);
    }
}
