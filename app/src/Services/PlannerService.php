<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PlannerItem;
use App\Models\PlannerSummary;
use App\Models\FlashType;
use App\Repositories\Interfaces\IEventRepository;
use App\Services\Interfaces\IPlannerService;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class PlannerService implements IPlannerService
{
    private const SESSION_KEY = 'planner';
    private const FLASH_KEY = 'planner_flash';
    private IEventRepository $events;

    public function __construct(IEventRepository $events)
    {
        $this->events = $events;
    }

    public function getItems(): array
    {
        return (array) ($this->getPlannerState()['items'] ?? []);
    }

    public function addItem(int $eventId, int $quantity, ?string $familyTicket): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);

        $event = $this->assertEventCanBePlanned($eventId);

        $planner = $this->getPlannerState();
        $currentItem = $planner['items'][$eventId] ?? ['quantity' => 0, 'familyTicket' => false];
        $current = $this->itemQuantity($currentItem);
        $requestedQuantity = $current + $quantity;
        $this->assertQuantityWithinAvailability($event, $requestedQuantity);

        $planner['items'][$eventId] = [
            'quantity' => $requestedQuantity,
            'familyTicket' => $familyTicket === 'on' || (bool) ($currentItem['familyTicket'] ?? false),
        ];

        $this->savePlanner($planner);
    }

    public function addItems(array $eventIds, int $quantity): int
    {
        $ids = array_values(array_unique(array_map('intval', $eventIds)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

        if ($ids === []) {
            throw new InvalidArgumentException('Select at least one valid event before adding it to your planner.');
        }

        $this->assertQuantity($quantity);

        $planner = $this->getPlannerState();

        foreach ($ids as $eventId) {
            $event = $this->assertEventCanBePlanned($eventId);
            $current = $this->itemQuantity($planner['items'][$eventId] ?? ['quantity' => 0, 'familyTicket' => false]);
            $requestedQuantity = $current + $quantity;
            $this->assertQuantityWithinAvailability($event, $requestedQuantity);
        }

        foreach ($ids as $eventId) {
            $currentItem = $planner['items'][$eventId] ?? ['quantity' => 0, 'familyTicket' => false];
            $planner['items'][$eventId] = [
                'quantity' => $this->itemQuantity($currentItem) + $quantity,
                'familyTicket' => (bool) ($currentItem['familyTicket'] ?? false),
            ];
        }

        $this->savePlanner($planner);

        return count($ids);
    }

    public function updateItemQuantity(int $eventId, int $quantity): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);

        $planner = $this->getPlannerState();

        if (!isset($planner['items'][$eventId])) {
            throw new RuntimeException('This event is not in your planner.');
        }

        $event = $this->assertEventCanBePlanned($eventId);
        $this->assertQuantityWithinAvailability($event, $quantity);

        $planner['items'][$eventId] = [
            'quantity' => $quantity,
            'familyTicket' => (bool) ($planner['items'][$eventId]['familyTicket'] ?? false),
        ];
        $this->savePlanner($planner);
    }

    public function removeItem(int $eventId): void
    {
        $this->assertEventId($eventId);

        $planner = $this->getPlannerState();
        unset($planner['items'][$eventId]);
        $this->savePlanner($planner);
    }

    public function clear(): void
    {
        $planner = $this->getPlannerState();
        $planner['items'] = [];
        $this->savePlanner($planner);
    }

    public function setFlash(FlashType $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY] = [
            'type' => $type->value,
            'message' => $message,
        ];
    }

    public function consumeFlash(): ?array
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return null;
        }

        $flash = $_SESSION[self::FLASH_KEY];
        unset($_SESSION[self::FLASH_KEY]);

        return [
            'type' => (string) ($flash['type'] ?? 'info'),
            'message' => (string) ($flash['message'] ?? ''),
        ];
    }

    public function getDetailedPlanner(): array
    {
        $items = $this->getItems();
        $eventIds = array_map('intval', array_keys($items));
        $eventsById = $this->events->findByIds($eventIds);

        // Filter free events using the already-fetched event map so we do not
        // fire a second findByIds query (previously done inside getItems).
        $filteredItems = $this->filterOutFreeItems($items, $eventsById);
        if ($filteredItems !== $items) {
            $planner = $this->getPlannerState();
            $planner['items'] = $filteredItems;
            $this->savePlanner($planner);
            $items = $filteredItems;
        }

        $summary = PlannerSummary::fromRawItems($items, $eventsById);

        // Convert PlannerItem objects to plain arrays so that views and
        // downstream services (CheckoutService) continue to receive the same
        // flat array shape they already rely on.
        $itemArrays = array_map(
            static fn(PlannerItem $p): array => $p->toArray(),
            $summary->plannerItems()
        );

        return [
            'items' => $itemArrays,
            'items_map' => $items,
            'total_quantity' => $summary->totalQuantity(),
            'total_price_value' => $summary->totalPriceValue(),
            'total_price' => $summary->formattedTotalPrice(),
            'is_empty' => $summary->isEmpty(),
            'has_invalid_items' => $summary->hasInvalidItems(),
            'invalid_item_ids' => $summary->invalidItemIds(),
            'time_conflicts' => $this->detectTimeConflicts($summary->conflictItems()),
            'time_conflict_pairs' => $this->detectTimeConflictPairs($summary->conflictItems()),
        ];
    }

    private function savePlanner(array $planner): void
    {
        $planner['updated_at'] = time();
        $_SESSION[self::SESSION_KEY] = [
            'items' => $this->normalizeItems((array) ($planner['items'] ?? [])),
            'updated_at' => (int) $planner['updated_at'],
        ];
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

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $eventIdRaw => $item) {
            $eventId = (int) $eventIdRaw;
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            $familyTicket = (bool) ($item['familyTicket'] ?? false);

            if ($eventId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[$eventId] = [
                'quantity' => $quantity,
                'familyTicket' => $familyTicket,
            ];
        }

        return $normalized;
    }

    private function getPlannerState(): array
    {
        $this->ensurePlannerState();

        return (array) $_SESSION[self::SESSION_KEY];
    }

    private function ensurePlannerState(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [
                'items' => [],
                'updated_at' => time(),
            ];
        }

        $_SESSION[self::SESSION_KEY]['items'] = $this->normalizeItems(
            (array) ($_SESSION[self::SESSION_KEY]['items'] ?? [])
        );
        $_SESSION[self::SESSION_KEY]['updated_at'] = (int) ($_SESSION[self::SESSION_KEY]['updated_at'] ?? time());
    }

    private function filterOutFreeItems(array $items, array $eventsById): array
    {
        $filtered = [];

        foreach ($items as $eventIdRaw => $item) {
            $eventId = (int) $eventIdRaw;
            $event = $eventsById[$eventId] ?? null;

            if ($event !== null && $event->isFree()) {
                continue;
            }

            $filtered[$eventId] = [
                'quantity' => (int) $item['quantity'],
                'familyTicket' => (bool) $item['familyTicket'],
            ];
        }

        return $filtered;
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

    private function detectTimeConflicts(array $items): array
    {
        return array_map(
            static fn(array $pair): string => $pair['message'],
            $this->findOverlappingPlannerItemPairs($items)
        );
    }

    private function detectTimeConflictPairs(array $items): array
    {
        return $this->findOverlappingPlannerItemPairs($items);
    }

    /**
     * Build the overlapping item pairs once, then reuse the result for both
     * the user-facing conflict messages and the structured conflict payload.
     *
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array{left_event_id:int,left_name:string,right_event_id:int,right_name:string,message:string}>
     */
    private function findOverlappingPlannerItemPairs(array $items): array
    {
        $pairs = [];
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            $leftStart = new DateTimeImmutable((string) $items[$i]['start_time']);
            $leftEnd = new DateTimeImmutable((string) $items[$i]['end_time']);

            for ($j = $i + 1; $j < $count; $j++) {
                $rightStart = new DateTimeImmutable((string) $items[$j]['start_time']);
                $rightEnd = new DateTimeImmutable((string) $items[$j]['end_time']);

                if ($leftStart < $rightEnd && $rightStart < $leftEnd) {
                    $pairs[] = [
                        'left_event_id' => (int) $items[$i]['event_id'],
                        'left_name' => (string) $items[$i]['name'],
                        'right_event_id' => (int) $items[$j]['event_id'],
                        'right_name' => (string) $items[$j]['name'],
                        'message' => sprintf(
                            '%s overlaps with %s.',
                            (string) $items[$i]['name'],
                            (string) $items[$j]['name']
                        ),
                    ];
                }
            }
        }

        return $pairs;
    }
}
