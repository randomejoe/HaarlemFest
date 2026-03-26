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
    public function isNameEditable(): bool
    {
        return true;
    }
    public function updateWithImage(int $id, array $postData, array $fileData): bool
    {
        $data = $postData;

        if (isset($_FILES['artist_img']) && $_FILES['artist_img']['error'] === UPLOAD_ERR_OK) {
            $data['artist_img'] = ImageUploader::handleImageUpload($_FILES['artist_img']);
        } else {
            $data['artist_img'] = $postData['artist_img'] ?? null;
        }

        $data['language'] = $data['language'] ?: NULL;
        $data['description'] = $data['description'] ?: NULL;

        return $this->repository->updateEvent($id, $data);
    }
    public function update(int $id, array $postData): bool
    {
        $postData['language'] = $postData['language'] ?: NULL;
        $postData['description'] = $postData['description'] ?: NULL;
        $postData['artist_img'] = $postData['artist_img'] ?? null;

        return $this->repository->updateEvent($id, $postData);
    }
    public function delete(int $id): bool
    {
        return $this->repository->deleteEvent($id);
    }

    public function getAll()
    {
        return $this->repository->getAllEvents();
    }
    public function getAllInCategory(string $category)
    {
        return $this->repository->getAllEventsInCategory($category);
    }

    public function getLineupDataForCategory(string $category): array
    {
        $events = $this->repository->findByCategory($category);

        usort($events, static function ($left, $right): int {
            $startComparison = $left->startsAt() <=> $right->startsAt();
            if ($startComparison !== 0) {
                return $startComparison;
            }

            return strcasecmp($left->getName(), $right->getName());
        });

        $venuePalette = ['hall-main', 'hall-second', 'hall-third', 'hall-market'];
        $venueClassMap = [];
        $days = [];

        foreach ($events as $event) {
            $dayKey = $event->startsAt()->format('Y-m-d');
            $stageLabel = trim((string) ($event->location() ?: $event->venue() ?: 'Venue to be announced'));
            $venueLabel = trim((string) ($event->venue() ?: $stageLabel));

            if (!isset($venueClassMap[$stageLabel])) {
                $venueClassMap[$stageLabel] = $venuePalette[count($venueClassMap) % count($venuePalette)];
            }

            if (!isset($days[$dayKey])) {
                $days[$dayKey] = [
                    'key' => $dayKey,
                    'label_day' => $event->startsAt()->format('D'),
                    'label_date' => $event->startsAt()->format('j M'),
                    'legend' => [],
                    'events' => [],
                ];
            }

            if (!isset($days[$dayKey]['legend'][$stageLabel])) {
                $days[$dayKey]['legend'][$stageLabel] = [
                    'label' => $stageLabel,
                    'class' => $venueClassMap[$stageLabel],
                ];
            }

            $days[$dayKey]['events'][] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
                'time' => $event->formattedTimeRange(),
                'description' => trim((string) ($event->description() ?? '')),
                'stage' => $stageLabel,
                'venue' => $venueLabel,
                'venue_class' => $venueClassMap[$stageLabel],
                'artist_img' => $event->artistImg(),
            ];
        }

        foreach ($days as &$day) {
            $day['legend'] = array_values($day['legend']);
        }
        unset($day);

        return array_values($days);
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
        $postData['language'] = $postData['language'] ?: NULL;
        $postData['description'] = $postData['description'] ?: NULL;

        return $this->repository->createSubEvent($category, $postData);
    }
}
