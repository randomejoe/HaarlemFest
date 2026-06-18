<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\Interfaces\IEventRepository;
use App\Repositories\Interfaces\IPageRepository;
use App\Services\Interfaces\IEventService;
use App\ViewModels\EventCardViewModel;

class EventService implements CMSServiceInterface, IEventService
{
    private IEventRepository $repository;
    private IPageRepository $pageRepository;

    public function __construct(IEventRepository $repository, IPageRepository $pageRepository)
    {
        $this->repository = $repository;
        $this->pageRepository = $pageRepository;
    }

    public function getForEdit(int $id): ?Event
    {
        return $this->repository->getEventForEdit($id);
    }

    public function isNameEditable(): bool
    {
        return true;
    }

    public function updateWithImage(int $id, array $postData, array $fileData): bool
    {
        return $this->repository->updateEvent($id, $this->normalizeEventData($postData, $fileData));
    }

    public function update(int $id, array $postData)
    {
        return $this->repository->updateEvent($id, $this->normalizeEventData($postData));
    }

    public function delete(int $id): bool
    {
        return $this->repository->deleteEvent($id);
    }

    public function getAll(array $params)
    {
        $validParams = ['event'];

        $filteredParams = array_intersect_key(
            $params,

            // $validParams is basically [0 => 'search', 1 => 'sort_by']
            array_flip($validParams)
        );

        if ($filteredParams && $filteredParams['event'] != '') {
            return $this->repository->getAllEventsInCategory($filteredParams['event']);
        }
        else {
           return $this->repository->getAllEvents(); 
        }
        
    }
    public function getAllInCategory(string $category)
    {
        return $this->repository->getAllEventsInCategory($category);
    }
    public function getAllFiltered(string $category)
    {
        return $this->repository->getAllEventsInCategory($category);
    }

    public function getSchedule(string $event): array {
        // $event is the main event which is the category
        $events =  $this->repository->getAllEventsInCategory($event);

        $schedule = [];
        foreach ($events as $event) {
            $date = date('Y-m-d', strtotime($event->startTime()));
            $time = date('H:i', strtotime($event->startTime()));
            $language = $event->getLanguage();
            $schedule[$date][$time][$language->value][] = $event;
        }

        return $schedule;
    }

    public function findById(int $eventId): ?Event
    {
        return $this->repository->findById($eventId);
    }

    public function findByName(string $eventName): array
    {
        return $this->repository->findByName($eventName);
    }

    public function getArtistScheduleData(string $artistName): EventCardViewModel
    {
        $artistName = trim($artistName);
        if ($artistName === '') {
            return EventCardViewModel::fromRows([]);
        }

        $events = $this->repository->findByName($artistName);

        usort($events, static function ($left, $right): int {
            $startComparison = $left->startsAt() <=> $right->startsAt();
            if ($startComparison !== 0) {
                return $startComparison;
            }

            return strcasecmp($left->getName(), $right->getName());
        });

        return EventCardViewModel::artistSchedule($events);
    }

    public function getArtistVenuesData(string $artistName): EventCardViewModel
    {
        $artistName = trim($artistName);
        if ($artistName === '') {
            return EventCardViewModel::fromRows([]);
        }

        $rows = $this->repository->findVenuesByArtist($artistName);
        if ($rows === []) {
            return EventCardViewModel::fromRows([]);
        }

        return EventCardViewModel::artistVenues($rows);
    }

    public function getLineupDataForCategory(string $category): EventCardViewModel
    {
        $events = $this->repository->findByCategory($category);

        usort($events, static function ($left, $right): int {
            $startComparison = $left->startsAt() <=> $right->startsAt();
            if ($startComparison !== 0) {
                return $startComparison;
            }

            return strcasecmp($left->getName(), $right->getName());
        });

        return EventCardViewModel::lineup($events);
    }

    public function getProgramDataForCategory(string $category): EventCardViewModel
    {
        $events = $this->repository->findByCategory($category);

        usort($events, static function ($left, $right): int {
            $startComparison = $left->startsAt() <=> $right->startsAt();
            if ($startComparison !== 0) {
                return $startComparison;
            }

            return strcasecmp($left->getName(), $right->getName());
        });

        return EventCardViewModel::program($events);
    }

    public function getCategories()
    {
        $categories = $this->pageRepository->getEventCategories();
        return array_column($categories, 'category');
    }

    public function create(array $postData)
    {
        // Main events need their own page and therefore are handled as pages with an extra tag
        $title = trim((string) ($postData['item_name'] ?? ''));
        return $this->pageRepository->createPage($title, 1);
    }
    public function createForCategory(string $category, array $postData)
    {
        return $this->repository->createSubEvent($category, $this->normalizeEventData($postData));
    }

    /**
     * Normalize nullable CMS fields and optionally resolve an uploaded artist image.
     *
     * @param array<string, mixed> $postData
     * @param array<string, array<string, mixed>>|null $fileData
     * @return array<string, mixed>
     */
    private function normalizeEventData(array $postData, ?array $fileData = null): array
    {
        $postData['language'] = $this->nullIfEmpty($postData['language'] ?? null);
        $postData['description'] = $this->nullIfEmpty($postData['description'] ?? null);

        if ($fileData !== null) {
            $postData['artist_img'] = $this->resolveArtistImage($postData, $fileData);
        } else {
            $postData['artist_img'] = $this->nullIfEmpty($postData['artist_img'] ?? null);
        }

        return $postData;
    }

    /**
     * @param array<string, mixed> $postData
     * @param array<string, array<string, mixed>> $fileData
     */
    private function resolveArtistImage(array $postData, array $fileData): ?string
    {
        $artistImage = $postData['artist_img'] ?? null;
        $uploadedImage = $fileData['artist_img'] ?? null;

        if (is_array($uploadedImage) && ($uploadedImage['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            return ImageUploader::upload($uploadedImage);
        }

        return $this->nullIfEmpty($artistImage);
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
