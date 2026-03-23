<?php

namespace App\Models;

/**
 * An immutable summary of everything in the personal planner.
 *
 * Build it with the static factory:
 *   PlannerSummary::fromRawItems($items, $eventsById)
 *
 * The factory does the same work that PlannerService::summarizePlannerItems()
 * used to do, so all the looping / totalling logic lives here instead of
 * being spread across PlannerService.
 */
class PlannerSummary
{
    /**
     * @param PlannerItem[]        $plannerItems
     * @param int[]                $invalidItemIds
     * @param array<int,array>     $conflictItems  Internal shape used by detectTimeConflicts()
     */
    private function __construct(
        private array $plannerItems,
        private array $invalidItemIds,
        private int   $totalQuantity,
        private float $totalPriceValue,
        private array $conflictItems,
    ) {
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * @param array<int,int>   $rawItems    eventId => quantity (from session)
     * @param array<int,Event> $eventsById
     */
    public static function fromRawItems(array $rawItems, array $eventsById): self
    {
        $plannerItems    = [];
        $invalidItemIds  = [];
        $totalQuantity   = 0;
        $totalPriceValue = 0.0;
        $conflictItems   = [];

        foreach ($rawItems as $eventIdRaw => $quantityRaw) {
            $eventId  = (int) $eventIdRaw;
            $quantity = max(0, (int) $quantityRaw);

            if ($eventId <= 0 || $quantity <= 0) {
                continue;
            }

            $totalQuantity += $quantity;

            $event = $eventsById[$eventId] ?? null;
            if ($event === null) {
                $invalidItemIds[] = $eventId;
                $plannerItems[]   = PlannerItem::unavailable($eventId, $quantity);
                continue;
            }

            $plannerItem    = PlannerItem::fromEvent($eventId, $quantity, $event);
            $plannerItems[] = $plannerItem;
            $totalPriceValue += $plannerItem->lineTotalValue();

            $conflictItems[] = [
                'event_id'   => $eventId,
                'name'       => $event->getName(),
                'start_time' => $event->startTime(),
                'end_time'   => $event->endTime(),
            ];
        }

        return new self($plannerItems, $invalidItemIds, $totalQuantity, $totalPriceValue, $conflictItems);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** @return PlannerItem[] */
    public function plannerItems(): array   { return $this->plannerItems; }

    /** @return int[] */
    public function invalidItemIds(): array { return $this->invalidItemIds; }

    public function totalQuantity(): int    { return $this->totalQuantity; }

    public function totalPriceValue(): float { return $this->totalPriceValue; }

    /** Raw conflict-item arrays consumed by PlannerService::detectTimeConflicts(). */
    public function conflictItems(): array  { return $this->conflictItems; }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isEmpty(): bool         { return $this->totalQuantity === 0; }

    public function hasInvalidItems(): bool { return $this->invalidItemIds !== []; }

    public function formattedTotalPrice(): string { return number_format($this->totalPriceValue, 2); }
}
