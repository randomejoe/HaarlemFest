<?php

namespace App\Services;

use App\Models\Event;
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
            $data['artist_img'] = ImageUploader::upload($_FILES['artist_img']);
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

    public function findById(int $eventId)
    {
        return $this->repository->findById($eventId);
    }

    public function findByName(string $eventName): array
    {
        return $this->repository->findByName($eventName);
    }

    public function getArtistScheduleData(string $artistName): array
    {
        $artistName = trim($artistName);
        if ($artistName === '') {
            return [];
        }

        $events = $this->repository->findByName($artistName);

        usort($events, static function ($left, $right): int {
            $startComparison = $left->startsAt() <=> $right->startsAt();
            if ($startComparison !== 0) {
                return $startComparison;
            }

            return strcasecmp($left->getName(), $right->getName());
        });

        $scheduleRows = [];

        foreach ($events as $index => $event) {
            $venue = $this->formatArtistScheduleVenue($event);
            $ticketState = $this->resolveArtistScheduleTicketState($event);
            $isFree = $event->isFree();

            $badgeLabel = '';
            $badgeClass = '';

            if ($isFree) {
                $badgeLabel = 'Free';
                $badgeClass = 'is-free';
            } elseif ($index === 0) {
                $badgeLabel = 'Next';
                $badgeClass = 'is-next';
            }

            $scheduleRows[] = [
                'id' => $event->getId(),
                'day_label' => $event->startsAt()->format('l'),
                'date_label' => $event->startsAt()->format('j F'),
                'time_label' => $event->formattedTimeRange(),
                'venue_label' => $venue['venue'],
                'location_label' => $venue['location'],
                'event_type' => $this->resolveArtistScheduleEventType($event),
                'tickets_label' => $ticketState['label'],
                'tickets_class' => $ticketState['class'],
                'badge_label' => $badgeLabel,
                'badge_class' => $badgeClass,
                'is_free' => $isFree,
                'is_highlighted' => $index === 0 && !$isFree,
                'can_add_to_planner' => $event->canBePlanned(),
            ];
        }

        return $scheduleRows;
    }

    public function getArtistVenuesData(string $artistName): array
    {
        $artistName = trim($artistName);
        if ($artistName === '') {
            return [];
        }

        $rows = $this->repository->findVenuesByArtist($artistName);
        if ($rows === []) {
            return [];
        }

        $venues = [];

        foreach ($rows as $row) {
            $venueId = isset($row['venue_id']) && $row['venue_id'] !== null ? (int) $row['venue_id'] : null;
            $venueLocation = trim((string) ($row['venue_location'] ?? ''));
            $eventLocation = trim((string) ($row['event_location'] ?? ''));
            $displayLocation = $venueLocation !== '' ? $venueLocation : $eventLocation;

            if ($displayLocation === '') {
                $displayLocation = 'Venue to be announced';
            }

            $venueKey = $venueId !== null && $venueId > 0
                ? 'id:' . $venueId
                : 'label:' . strtolower($displayLocation);

            if (!isset($venues[$venueKey])) {
                $fallbackProfile = $this->resolveArtistVenueProfile($displayLocation);
                $capacity = isset($row['venue_capacity']) && $row['venue_capacity'] !== null
                    ? max(0, (int) $row['venue_capacity'])
                    : null;

                $venues[$venueKey] = [
                    'venue_id' => $venueId,
                    'name' => $displayLocation,
                    'stage_label' => $fallbackProfile['stage_label'],
                    'capacity_label' => $capacity !== null ? number_format($capacity) : $fallbackProfile['capacity_label'],
                    'description' => $fallbackProfile['description'],
                    'address' => $fallbackProfile['address'] !== '' ? $fallbackProfile['address'] : $displayLocation,
                    'facilities' => $fallbackProfile['facilities'],
                ];
            }
        }

        return array_values($venues);
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

    public function getProgramDataForCategory(string $category): array
    {
        $events = $this->repository->findByCategory($category);

        usort($events, static function ($left, $right): int {
            $startComparison = $left->startsAt() <=> $right->startsAt();
            if ($startComparison !== 0) {
                return $startComparison;
            }

            return strcasecmp($left->getName(), $right->getName());
        });

        $days = [];

        foreach ($events as $event) {
            $dayKey   = $event->startsAt()->format('Y-m-d');
            $seatCount = $event->seatCount();
            $availabilityLabel = null;
            $status    = null;
            $statusClass = '';

            if ($event->hasTrackedStock()) {
                if (($seatCount ?? 0) > 0) {
                    $availabilityLabel = sprintf(
                        '%d %s available',
                        $seatCount,
                        $seatCount === 1 ? 'seat' : 'seats'
                    );
                } else {
                    $status      = 'Sold out';
                    $statusClass = 'sold-out';
                }
            }

            if (!isset($days[$dayKey])) {
                $day = new \DateTimeImmutable($dayKey);
                $days[$dayKey] = [
                    'key'        => $dayKey,
                    'label_day'  => strtoupper($day->format('D')),
                    'label_date' => $day->format('j M'),
                    'events'     => [],
                ];
            }

            $days[$dayKey]['events'][] = [
                'event_id'           => $event->getId(),
                'name'               => $event->getName(),
                'time'               => $event->formattedTimeRange(),
                'venue'              => $event->venue(),
                'description'        => $event->description(),
                'availability_label' => $availabilityLabel,
                'seat_count'         => $seatCount,
                'status'             => $status,
                'status_class'       => $statusClass,
                'is_free'            => $event->isFree(),
                'can_add_to_planner' => $event->canBePlanned(),
                'price'              => number_format((float) $event->ticketPrice(), 2),
            ];
        }

        return array_values($days);
    }

    public function getCategories()
    {
        $categories = $this->pageRepository->getEventCategories();
        return array_column($categories, 'category');
    }

    private function formatArtistScheduleVenue(Event $event): array
    {
        $venue = trim((string) ($event->venue() ?? ''));
        $location = trim((string) ($event->location() ?? ''));

        if ($venue === '' && $location === '') {
            return [
                'venue' => 'Venue to be announced',
                'location' => '',
            ];
        }

        if ($venue === '') {
            return [
                'venue' => $location,
                'location' => '',
            ];
        }

        if ($location === '' || strcasecmp($venue, $location) === 0) {
            return [
                'venue' => $venue,
                'location' => '',
            ];
        }

        return [
            'venue' => $venue,
            'location' => $location,
        ];
    }

    private function resolveArtistScheduleEventType(Event $event): string
    {
        $venueContext = strtolower(trim((string) (($event->venue() ?? '') . ' ' . ($event->location() ?? ''))));

        if ($event->isFree() || str_contains($venueContext, 'markt') || str_contains($venueContext, 'square')) {
            return 'Open-air';
        }

        return 'Club Session';
    }

    private function resolveArtistScheduleTicketState(Event $event): array
    {
        if ($event->isFree()) {
            return [
                'label' => 'Free entry',
                'class' => 'is-free',
            ];
        }

        if ($event->isSoldOut()) {
            return [
                'label' => 'Sold out',
                'class' => 'is-sold-out',
            ];
        }

        return [
            'label' => 'Regular ticket or Day Pass',
            'class' => 'is-paid',
        ];
    }

    /**
     * Defaults for details not stored in the DB.
     * These values are keyed by venue location label and can be overridden later in CMS if needed.
     *
     * @return array{stage_label: string, capacity_label: string, description: string, address: string, facilities: string}
     */
    private function resolveArtistVenueProfile(string $venueLocation): array
    {
        $normalizedLocation = strtolower(trim($venueLocation));

        if (str_contains($normalizedLocation, 'patronaat')) {
            return [
                'stage_label' => 'Main Hall',
                'capacity_label' => '',
                'description' => 'Intimate club atmosphere in a legendary music venue',
                'address' => 'Zijlsingel 2, 2013 DN Haarlem',
                'facilities' => 'Wheelchair accessible, nearby parking, 5 min from Haarlem CS',
            ];
        }

        if (str_contains($normalizedLocation, 'grote markt')) {
            return [
                'stage_label' => 'Main Stage',
                'capacity_label' => '',
                'description' => 'Open-air main square in the heart of historic Haarlem',
                'address' => 'Grote Markt, 2011 RD Haarlem',
                'facilities' => 'Fully accessible, public transport hub, free admission',
            ];
        }

        return [
            'stage_label' => 'Festival Stage',
            'capacity_label' => '',
            'description' => 'Festival venue in Haarlem.',
            'address' => '',
            'facilities' => 'Accessibility details available at the venue.',
        ];
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
