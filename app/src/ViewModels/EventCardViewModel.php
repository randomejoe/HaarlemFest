<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Event;
use DateTimeImmutable;

final class EventCardViewModel
{
    private function __construct(private array $rows)
    {
    }

    public static function fromRows(array $rows): self
    {
        return new self($rows);
    }

    /**
     * @param Event[] $events
     */
    public static function artistSchedule(array $events): self
    {
        $rows = [];

        foreach ($events as $index => $event) {
            $venue = self::artistScheduleVenue($event);
            $ticketState = self::artistScheduleTicketState($event);
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

            $rows[] = [
                'id' => $event->getId(),
                'day_label' => $event->startsAt()->format('l'),
                'date_label' => $event->startsAt()->format('j F'),
                'time_label' => $event->formattedTimeRange(),
                'venue_label' => $venue['venue'],
                'location_label' => $venue['location'],
                'event_type' => self::artistScheduleEventType($event),
                'tickets_label' => $ticketState['label'],
                'tickets_class' => $ticketState['class'],
                'badge_label' => $badgeLabel,
                'badge_class' => $badgeClass,
                'is_free' => $isFree,
                'is_highlighted' => $index === 0 && !$isFree,
                'can_add_to_planner' => $event->canBePlanned(),
            ];
        }

        return new self($rows);
    }

    /**
     * @param Event[] $events
     */
    public static function lineup(array $events): self
    {
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

        return new self(array_values($days));
    }

    public static function artistVenues(array $rows): self
    {
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
                $fallbackProfile = self::artistVenueProfile($displayLocation);
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

        return new self(array_values($venues));
    }

    /**
     * @param Event[] $events
     */
    public static function program(array $events): self
    {
        $days = [];

        foreach ($events as $event) {
            $dayKey = $event->startsAt()->format('Y-m-d');
            $seatCount = $event->seatCount();
            $availabilityLabel = null;
            $status = null;
            $statusClass = '';

            if ($event->hasTrackedStock()) {
                if (($seatCount ?? 0) > 0) {
                    $availabilityLabel = sprintf(
                        '%d %s available',
                        $seatCount,
                        $seatCount === 1 ? 'seat' : 'seats'
                    );
                } else {
                    $status = 'Sold out';
                    $statusClass = 'sold-out';
                }
            }

            if (!isset($days[$dayKey])) {
                $day = new DateTimeImmutable($dayKey);
                $days[$dayKey] = [
                    'key' => $dayKey,
                    'label_day' => strtoupper($day->format('D')),
                    'label_date' => $day->format('j M'),
                    'events' => [],
                ];
            }

            $days[$dayKey]['events'][] = [
                'event_id' => $event->getId(),
                'name' => $event->getName(),
                'time' => $event->formattedTimeRange(),
                'venue' => $event->venue(),
                'description' => $event->description(),
                'availability_label' => $availabilityLabel,
                'seat_count' => $seatCount,
                'status' => $status,
                'status_class' => $statusClass,
                'is_free' => $event->isFree(),
                'can_add_to_planner' => $event->canBePlanned(),
                'price' => number_format((float) $event->ticketPrice(), 2),
            ];
        }

        return new self(array_values($days));
    }

    public function toArray(): array
    {
        return $this->rows;
    }

    private static function artistScheduleVenue(Event $event): array
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

    private static function artistScheduleEventType(Event $event): string
    {
        $venueContext = strtolower(trim((string) (($event->venue() ?? '') . ' ' . ($event->location() ?? ''))));

        if ($event->isFree() || str_contains($venueContext, 'markt') || str_contains($venueContext, 'square')) {
            return 'Open-air';
        }

        return 'Club Session';
    }

    private static function artistScheduleTicketState(Event $event): array
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

    private static function artistVenueProfile(string $venueLocation): array
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
}
