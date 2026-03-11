<?php

namespace App\Controllers;

use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\PlannerService;
use App\View;

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
        $events = $this->events->findByCategory('jazz');
        $programByDay = [];

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

            if (!isset($programByDay[$dayKey])) {
                $programByDay[$dayKey] = [];
            }

            $programByDay[$dayKey][] = $this->mapEventForView($event, $availabilityLabel, $status, $statusClass);
        }

        $days = [];
        foreach (array_keys($programByDay) as $dayKey) {
            $day = new \DateTimeImmutable($dayKey);
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

    private function mapEventForView(Event $event, ?string $availabilityLabel, ?string $status, string $statusClass): array
    {
        return [
            'event_id' => $event->id(),
            'name' => $event->name(),
            'time' => $event->formattedTimeRange(),
            'venue' => $event->venue(),
            'description' => $event->description(),
            'availability_label' => $availabilityLabel,
            'seat_count' => $event->seatCount(),
            'status' => $status,
            'status_class' => $statusClass,
            'is_free' => $event->isFree(),
            'can_add_to_planner' => $event->canBePlanned(),
            'price_value' => $event->ticketPrice(),
            'price' => number_format($event->ticketPrice(), 2),
        ];
    }
}
