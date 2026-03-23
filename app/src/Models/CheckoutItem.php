<?php

namespace App\Models;

/**
 * A validated, priced line item ready to be saved in a checkout attempt.
 *
 * Build via the static factory:
 *   CheckoutItem::fromPlannerArray($plannerItemArray)
 *
 * The array shape it accepts is the one produced by PlannerItem::toArray().
 * Call toArray() to get the flat shape that CheckoutRepository and
 * TicketHoldRepository expect.
 */
class CheckoutItem
{
    private function __construct(
        private int   $eventId,
        private int   $quantity,
        private float $unitPrice,
        private float $lineTotal,
    ) {
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Build from a single planner-item array (the shape returned by
     * PlannerItem::toArray() / PlannerService::getDetailedPlanner()).
     */
    public static function fromPlannerArray(array $item): self
    {
        return new self(
            eventId:   (int)   ($item['event_id']         ?? 0),
            quantity:  (int)   ($item['quantity']          ?? 0),
            unitPrice: (float) ($item['unit_price_value']  ?? 0.0),
            lineTotal: (float) ($item['line_total_value']  ?? 0.0),
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function eventId(): int    { return $this->eventId; }
    public function quantity(): int   { return $this->quantity; }
    public function unitPrice(): float { return $this->unitPrice; }
    public function lineTotal(): float { return $this->lineTotal; }

    // -------------------------------------------------------------------------
    // Array export (keeps repository contracts unchanged)
    // -------------------------------------------------------------------------

    /** Shape expected by CheckoutRepository::createAttemptItems() and TicketHoldRepository::createHolds(). */
    public function toArray(): array
    {
        return [
            'event_id'   => $this->eventId,
            'quantity'   => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
        ];
    }
}
