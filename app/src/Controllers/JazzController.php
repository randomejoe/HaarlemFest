<?php

namespace App\Controllers;

use App\Repositories\EventRepository;
use App\Services\PlannerService;
use App\View;
use DateTimeImmutable;

class JazzController
{
    private EventRepository $events;
    private PlannerService $planner;

    public function __construct(EventRepository $events, PlannerService $planner)
    {
        $this->events = $events;
        $this->planner = $planner;
    }

    public function showProgram(): void
    {
        $rows = $this->events->findByCategory('jazz');
        $programByDay = [];

        foreach ($rows as $row) {
            $startsAt = new DateTimeImmutable((string) $row['start_time']);
            $endsAt = new DateTimeImmutable((string) $row['end_time']);
            $dayKey = $startsAt->format('Y-m-d');
            $hasTrackedStock = $row['ticket_amount'] !== null;
            $seatCount = $hasTrackedStock ? max(0, (int) $row['ticket_amount']) : null;
            $price = isset($row['ticket_price']) ? (float) $row['ticket_price'] : 0.0;
            $isFree = $price <= 0.0;
            $availabilityLabel = null;
            $status = null;
            $statusClass = '';

            if ($hasTrackedStock) {
                if ($seatCount > 0) {
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

            if (!isset($programByDay[$dayKey])) {
                $programByDay[$dayKey] = [];
            }

            $programByDay[$dayKey][] = [
                'event_id' => (int) $row['event_id'],
                'name' => (string) $row['name'],
                'time' => $startsAt->format('H:i') . ' - ' . $endsAt->format('H:i'),
                'venue' => (string) ($row['venue_location'] ?? 'Venue to be announced'),
                'description' => (string) ($row['description'] ?? ''),
                'availability_label' => $availabilityLabel,
                'seat_count' => $seatCount,
                'status' => $status,
                'status_class' => $statusClass,
                'is_free' => $isFree,
                'can_add_to_planner' => !$isFree && (!$hasTrackedStock || $seatCount > 0),
                'price_value' => $price,
                'price' => number_format($price, 2),
            ];
        }

        $days = [];
        foreach (array_keys($programByDay) as $dayKey) {
            $day = new DateTimeImmutable($dayKey);
            $days[] = [
                'key' => $dayKey,
                'label_day' => strtoupper($day->format('D')),
                'label_date' => $day->format('j M'),
            ];
        }

        $selectedDay = (string) ($_GET['day'] ?? '');
        if ($selectedDay === '' || !isset($programByDay[$selectedDay])) {
            $selectedDay = $days[0]['key'] ?? '';
        }

        $selectedEvents = $selectedDay !== '' ? ($programByDay[$selectedDay] ?? []) : [];
        $plannerDetails = $this->planner->getDetailedPlanner();
        $plannerFlash = $this->planner->consumeFlash();

        echo View::render('jazz', [
            'days' => $days,
            'selected_day' => $selectedDay,
            'events' => $selectedEvents,
            'planner_count' => (int) $plannerDetails['total_quantity'],
            'planner_total' => (string) $plannerDetails['total_price'],
            'planner_locked' => (bool) $plannerDetails['is_locked'],
            'planner_flash' => $plannerFlash,
        ]);
    }
}
