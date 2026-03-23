<?php

namespace App\Models;

/**
 * Represents a single item in the personal planner (shopping basket).
 *
 * Build via the two static factories:
 *   PlannerItem::fromEvent(...)   – for a valid, bookable event
 *   PlannerItem::unavailable(...) – for an event that can no longer be found
 *
 * Call toArray() to get the flat array shape that views and CheckoutService expect.
 */
class PlannerItem
{
    private function __construct(
        private int     $eventId,
        private int     $quantity,
        private bool    $isValid,
        private ?string $invalidReason,
        private string  $name,
        private string  $venue,
        private string  $time,
        private ?string $startTime,
        private ?string $endTime,
        private float   $unitPriceValue,
        private float   $lineTotalValue,
        private int     $seatCount,
    ) {
    }

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    public static function fromEvent(int $eventId, int $quantity, Event $event): self
    {
        $unitPrice = $event->ticketPrice();

        return new self(
            eventId:       $eventId,
            quantity:      $quantity,
            isValid:       true,
            invalidReason: null,
            name:          $event->getName(),
            venue:         $event->venue(),
            time:          $event->formattedPlannerTime(),
            startTime:     $event->startTime(),
            endTime:       $event->endTime(),
            unitPriceValue: $unitPrice,
            lineTotalValue: $unitPrice * $quantity,
            seatCount:     max(0, (int) ($event->seatCount() ?? 0)),
        );
    }

    public static function unavailable(int $eventId, int $quantity): self
    {
        return new self(
            eventId:       $eventId,
            quantity:      $quantity,
            isValid:       false,
            invalidReason: 'This event is no longer available.',
            name:          'Event unavailable',
            venue:         'Unknown venue',
            time:          'Unknown time',
            startTime:     null,
            endTime:       null,
            unitPriceValue: 0.0,
            lineTotalValue: 0.0,
            seatCount:     0,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function eventId(): int       { return $this->eventId; }
    public function quantity(): int      { return $this->quantity; }
    public function isValid(): bool      { return $this->isValid; }
    public function lineTotalValue(): float { return $this->lineTotalValue; }
    public function startTime(): ?string { return $this->startTime; }
    public function endTime(): ?string   { return $this->endTime; }
    public function name(): string       { return $this->name; }

    // -------------------------------------------------------------------------
    // Array export (keeps the view / CheckoutService contract unchanged)
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'event_id'         => $this->eventId,
            'quantity'         => $this->quantity,
            'is_valid'         => $this->isValid,
            'invalid_reason'   => $this->invalidReason,
            'name'             => $this->name,
            'venue'            => $this->venue,
            'time'             => $this->time,
            'start_time'       => $this->startTime,
            'end_time'         => $this->endTime,
            'unit_price_value' => $this->unitPriceValue,
            'unit_price'       => number_format($this->unitPriceValue, 2),
            'line_total_value' => $this->lineTotalValue,
            'line_total'       => number_format($this->lineTotalValue, 2),
            'seat_count'       => $this->seatCount,
        ];
    }
}
