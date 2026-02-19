<?php

namespace App\Controllers;

use App\Repositories\EventRepository;
use DateTimeImmutable;

class JazzController
{
    private EventRepository $events;

    public function __construct()
    {
        $this->events = new EventRepository();
    }

    public function showProgram(): void
    {
        $rows = $this->events->findByCategory('jazz');
        $programByDay = [];

        foreach ($rows as $row) {
            $startsAt = new DateTimeImmutable((string) $row['start_time']);
            $endsAt = new DateTimeImmutable((string) $row['end_time']);
            $dayKey = $startsAt->format('Y-m-d');
            $seatCount = max(0, (int) ($row['ticket_amount'] ?? 0));
            $price = isset($row['ticket_price']) ? (float) $row['ticket_price'] : 0.0;

            if (!isset($programByDay[$dayKey])) {
                $programByDay[$dayKey] = [];
            }

            $programByDay[$dayKey][] = [
                'event_id' => (int) $row['event_id'],
                'name' => (string) $row['name'],
                'time' => $startsAt->format('H:i') . ' - ' . $endsAt->format('H:i'),
                'venue' => (string) ($row['venue_location'] ?? 'Venue to be announced'),
                'description' => (string) ($row['description'] ?? ''),
                'seat_count' => $seatCount,
                'status' => $seatCount > 0 ? 'Available' : 'Sold out',
                'status_class' => $seatCount > 0 ? 'available' : 'sold-out',
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
        $plannerCount = count($selectedEvents);
        $plannerTotal = 0.0;

        foreach ($selectedEvents as $event) {
            $plannerTotal += (float) $event['price_value'];
        }

        extract([
            'days' => $days,
            'selected_day' => $selectedDay,
            'events' => $selectedEvents,
            'planner_count' => $plannerCount,
            'planner_total' => number_format($plannerTotal, 2),
        ], EXTR_SKIP);
        require(__DIR__ . '/../Views/jazz.php');
    }
}
