<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PlannerItem;
use App\Models\PlannerSummary;
use App\Models\FlashType;
use App\Repositories\Interfaces\IEventRepository;
use App\Services\Interfaces\IPlannerService;
use InvalidArgumentException;
use RuntimeException;

class PlannerService implements IPlannerService
{
    private IEventRepository $events;
    private SessionManager $session;

    public function __construct(IEventRepository $events, SessionManager $session)
    {
        $this->events = $events;
        $this->session = $session;
    }

    public function getItems(): array
    {
        return $this->session->getPlannerItems();
    }

    public function addItem(int $eventId, int $quantity, ?string $familyTicket): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);

        $event = $this->assertEventCanBePlanned($eventId);

        $items = $this->getItems();
        $currentItem = $items[$eventId] ?? ['quantity' => 0, 'familyTicket' => false];
        $current = $this->itemQuantity($currentItem);
        $requestedQuantity = $current + $quantity;
        $this->assertQuantityWithinAvailability($event, $requestedQuantity);

        $items[$eventId] = [
            'quantity' => $requestedQuantity,
            'familyTicket' => $this->isFamilyTicketSelected($familyTicket)
                || (bool) ($currentItem['familyTicket'] ?? false),
        ];

        $this->session->setPlannerItems($items);
    }

    public function addItems(array $eventIds, int $quantity): int
    {
        $rows = $this->normalizeBulkRows($eventIds);
        $ids = array_keys($rows);

        if ($ids === []) {
            throw new InvalidArgumentException('Select at least one valid event before adding it to your planner.');
        }

        $this->assertQuantity($quantity);

        $items = $this->getItems();

        foreach ($ids as $eventId) {
            $event = $this->assertEventCanBePlanned($eventId);
            $current = $this->itemQuantity($items[$eventId] ?? ['quantity' => 0, 'familyTicket' => false]);
            $requestedQuantity = $current + $quantity;
            $this->assertQuantityWithinAvailability($event, $requestedQuantity);
        }

        foreach ($ids as $eventId) {
            $currentItem = $items[$eventId] ?? ['quantity' => 0, 'familyTicket' => false];
            $items[$eventId] = [
                'quantity' => $this->itemQuantity($currentItem) + $quantity,
                'familyTicket' => (bool) $rows[$eventId]['familyTicket']
                    || (bool) ($currentItem['familyTicket'] ?? false),
            ];
        }

        $this->session->setPlannerItems($items);

        return count($ids);
    }

    public function updateItemQuantity(int $eventId, int $quantity): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);

        $items = $this->getItems();

        if (!isset($items[$eventId])) {
            throw new RuntimeException('This event is not in your planner.');
        }

        $event = $this->assertEventCanBePlanned($eventId);
        $this->assertQuantityWithinAvailability($event, $quantity);

        $items[$eventId] = [
            'quantity' => $quantity,
            'familyTicket' => (bool) ($items[$eventId]['familyTicket'] ?? false),
        ];
        $this->session->setPlannerItems($items);
    }

    public function removeItem(int $eventId): void
    {
        $this->assertEventId($eventId);

        $this->session->removePlannerItem((string) $eventId);
    }

    public function clear(): void
    {
        $this->session->clearPlanner();
    }

    public function setFlash(FlashType $type, string $message): void
    {
        $this->session->setFlash($type, $message);
    }

    public function consumeFlash(): ?array
    {
        return $this->session->consumeFlash();
    }

    public function getPlannerSummary(): PlannerSummary
    {
        $items = $this->getItems();
        $eventsById = $this->events->findByIds(array_map('intval', array_keys($items)));
        $filteredItems = $this->filterOutUnavailableItems($items, $eventsById);

        if ($filteredItems !== $items) {
            $this->session->setPlannerItems($filteredItems);
            $items = $filteredItems;
        }

        $items = $this->withLineTotals($items, $eventsById);

        return PlannerSummary::fromRawItems($items, $eventsById);
    }

    public function pruneUnavailableItems(): void
    {
        $items = $this->getItems();
        $eventsById = $this->events->findByIds(array_map('intval', array_keys($items)));
        $filteredItems = $this->filterOutUnavailableItems($items, $eventsById);

        if ($filteredItems !== $items) {
            $this->session->setPlannerItems($filteredItems);
        }
    }

    public function getDetailedPlanner(): array
    {
        $summary = $this->getPlannerSummary();

        return array_merge($summary->toArray(), [
            'items_map' => $this->getItems(),
        ]);
    }

    private function assertEventId(int $eventId): void
    {
        if ($eventId <= 0) {
            throw new InvalidArgumentException('Invalid event selection.');
        }
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }

    private function itemQuantity(array $item): int
    {
        return max(0, (int) ($item['quantity'] ?? 0));
    }

    private function filterOutUnavailableItems(array $items, array $eventsById): array
    {
        $filtered = [];

        foreach ($items as $eventIdRaw => $item) {
            $eventId = (int) $eventIdRaw;
            $event = $eventsById[$eventId] ?? null;

            if ($event !== null && ($event->isFree() || $event->isSoldOut())) {
                continue;
            }

            $filtered[$eventId] = [
                'quantity' => (int) $item['quantity'],
                'familyTicket' => (bool) $item['familyTicket'],
            ];
        }

        return $filtered;
    }

    private function normalizeBulkRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $key => $row) {
            if (is_array($row)) {
                $eventId = (int) ($row['event_id'] ?? $row['id'] ?? $key);
                $familyTicket = (bool) ($row['familyTicket'] ?? false);
            } else {
                $eventId = (int) $row;
                $familyTicket = false;
            }

            if ($eventId > 0) {
                $normalized[$eventId] = ['familyTicket' => $familyTicket];
            }
        }

        return $normalized;
    }

    private function isFamilyTicketSelected(?string $familyTicket): bool
    {
        if ($familyTicket === null) {
            return false;
        }

        return in_array(strtolower($familyTicket), ['1', 'true', 'yes', 'on'], true);
    }

    private function withLineTotals(array $items, array $eventsById): array
    {
        foreach ($items as $eventId => $item) {
            $event = $eventsById[(int) $eventId] ?? null;
            if (!$event instanceof Event) {
                continue;
            }

            $items[$eventId]['line_total_value'] = $this->calculateLineTotal(
                (int) ($item['quantity'] ?? 0),
                $event,
                (bool) ($item['familyTicket'] ?? false)
            );
        }

        return $items;
    }

    private function calculateLineTotal(int $quantity, Event $event, bool $familyTicket): float
    {
        if ($familyTicket) {
            return (float) (60 * ceil($quantity / 4));
        }

        return $event->ticketPrice() * $quantity;
    }

    private function assertEventCanBePlanned(int $eventId): Event
    {
        $event = $this->events->findById($eventId);
        if ($event === null) {
            throw new RuntimeException('This event is no longer available.');
        }

        if ($event->isFree()) {
            throw new RuntimeException('Free events are not added to the planner.');
        }

        if ($event->isSoldOut()) {
            throw new RuntimeException('This event is sold out.');
        }

        return $event;
    }

    private function assertQuantityWithinAvailability(Event $event, int $requestedQuantity): void
    {
        if (!$event->hasTrackedStock()) {
            return;
        }

        $availableSeats = $event->seatCount() ?? 0;
        if ($requestedQuantity <= $availableSeats) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Only %d %s available for this event.',
            $availableSeats,
            $availableSeats === 1 ? 'seat is' : 'seats are'
        ));
    }

}
