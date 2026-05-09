<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class PlannerSummary
{
    /**
     * @param PlannerItem[] $plannerItems
     * @param int[] $invalidItemIds
     * @param array<int, array<string, mixed>> $conflictItems
     */
    private function __construct(
        private array $plannerItems,
        private array $invalidItemIds,
        private int $totalQuantity,
        private float $totalPriceValue,
        private array $conflictItems
    ) {
    }

    /**
     * @param array<int, array{quantity:int, familyTicket:bool}> $rawItems
     * @param array<int, Event> $eventsById
     */
    public static function fromRawItems(array $rawItems, array $eventsById): self
    {
        $plannerItems = [];
        $invalidItemIds = [];
        $totalQuantity = 0;
        $totalPriceValue = 0.0;
        $conflictItems = [];

        foreach ($rawItems as $eventIdRaw => $item) {
            $eventId = (int) $eventIdRaw;
            if ($eventId <= 0) {
                continue;
            }

            $familyTicket = (bool) ($item['familyTicket'] ?? false);
            $quantity = max(0, (int) ($item['quantity'] ?? 0));

            if ($quantity <= 0) {
                continue;
            }

            $totalQuantity += $quantity;

            $event = $eventsById[$eventId] ?? null;
            if (!$event instanceof Event) {
                $invalidItemIds[] = $eventId;
                $plannerItems[] = PlannerItem::unavailable($eventId, $quantity);
                continue;
            }

            $plannerItem = PlannerItem::fromEvent($eventId, $quantity, $event, $familyTicket);
            $plannerItems[] = $plannerItem;
            $totalPriceValue += $plannerItem->lineTotalValue();

            $conflictItems[] = [
                'event_id' => $eventId,
                'name' => $event->getName(),
                'start_time' => $event->startTime(),
                'end_time' => $event->endTime(),
            ];
        }

        return new self($plannerItems, $invalidItemIds, $totalQuantity, $totalPriceValue, $conflictItems);
    }

    /**
     * @return PlannerItem[]
     */
    public function plannerItems(): array
    {
        return $this->plannerItems;
    }

    /**
     * @return int[]
     */
    public function invalidItemIds(): array
    {
        return $this->invalidItemIds;
    }

    public function totalQuantity(): int
    {
        return $this->totalQuantity;
    }

    public function totalPriceValue(): float
    {
        return $this->totalPriceValue;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function conflictItems(): array
    {
        return $this->conflictItems;
    }

    public function isEmpty(): bool
    {
        return $this->totalQuantity === 0;
    }

    public function hasInvalidItems(): bool
    {
        return $this->invalidItemIds !== [];
    }

    public function formattedTotalPrice(): string
    {
        return number_format($this->totalPriceValue, 2);
    }

    /**
     * @return array<int,array{left_event_id:int,left_name:string,right_event_id:int,right_name:string,message:string}>
     */
    public function timeConflictPairs(): array
    {
        $pairs = [];
        $items = $this->conflictItems;
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            if (empty($items[$i]['start_time']) || empty($items[$i]['end_time'])) {
                continue;
            }
            $leftStart = new DateTimeImmutable((string) $items[$i]['start_time']);
            $leftEnd   = new DateTimeImmutable((string) $items[$i]['end_time']);

            for ($j = $i + 1; $j < $count; $j++) {
                if (empty($items[$j]['start_time']) || empty($items[$j]['end_time'])) {
                    continue;
                }
                $rightStart = new DateTimeImmutable((string) $items[$j]['start_time']);
                $rightEnd   = new DateTimeImmutable((string) $items[$j]['end_time']);

                if ($leftStart < $rightEnd && $rightStart < $leftEnd) {
                    $pairs[] = [
                        'left_event_id'  => (int) $items[$i]['event_id'],
                        'left_name'      => (string) $items[$i]['name'],
                        'right_event_id' => (int) $items[$j]['event_id'],
                        'right_name'     => (string) $items[$j]['name'],
                        'message'        => sprintf(
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

    /** @return string[] */
    public function timeConflicts(): array
    {
        return array_map(
            static fn(array $pair): string => $pair['message'],
            $this->timeConflictPairs()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn(PlannerItem $item): array => $item->toArray(),
                $this->plannerItems
            ),
            'total_quantity' => $this->totalQuantity,
            'total_price_value' => $this->totalPriceValue,
            'total_price' => $this->formattedTotalPrice(),
            'is_empty' => $this->isEmpty(),
            'has_invalid_items' => $this->hasInvalidItems(),
            'invalid_item_ids' => $this->invalidItemIds,
            'time_conflicts' => $this->timeConflicts(),
            'time_conflict_pairs' => $this->timeConflictPairs(),
        ];
    }
}
