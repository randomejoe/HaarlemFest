<?php

declare(strict_types=1);

namespace App\Models;

final class PlannerItem
{
    private function __construct(
        private int $eventId,
        private int $quantity,
        private bool $isValid,
        private ?string $invalidReason,
        private string $name,
        private string $venue,
        private string $time,
        private ?string $startTime,
        private ?string $endTime,
        private float $unitPriceValue,
        private float $lineTotalValue,
        private int $seatCount,
        private bool $familyTicket
    ) {
    }

    public static function fromEvent(int $eventId, int $quantity, Event $event, bool $familyTicket = false): self
    {
        $unitPrice = $event->ticketPrice();
        $lineTotal = $unitPrice * $quantity;

        if ($familyTicket) {
            $lineTotal = 60 * ceil($quantity / 4);
        }

        return new self(
            eventId: $eventId,
            quantity: $quantity,
            isValid: true,
            invalidReason: null,
            name: $event->getName(),
            venue: (string) ($event->venue() ?? 'Venue to be announced'),
            time: $event->formattedPlannerTime(),
            startTime: $event->startTime(),
            endTime: $event->endTime(),
            unitPriceValue: $unitPrice,
            lineTotalValue: (float) $lineTotal,
            seatCount: max(0, (int) ($event->seatCount() ?? 0)),
            familyTicket: $familyTicket
        );
    }

    public static function unavailable(int $eventId, int $quantity, ?string $reason = null): self
    {
        return new self(
            eventId: $eventId,
            quantity: $quantity,
            isValid: false,
            invalidReason: $reason ?? 'This event is no longer available.',
            name: 'Event unavailable',
            venue: 'Unknown venue',
            time: 'Unknown time',
            startTime: null,
            endTime: null,
            unitPriceValue: 0.0,
            lineTotalValue: 0.0,
            seatCount: 0,
            familyTicket: false
        );
    }

    public function eventId(): int
    {
        return $this->eventId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function invalidReason(): ?string
    {
        return $this->invalidReason;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function venue(): string
    {
        return $this->venue;
    }

    public function time(): string
    {
        return $this->time;
    }

    public function startTime(): ?string
    {
        return $this->startTime;
    }

    public function endTime(): ?string
    {
        return $this->endTime;
    }

    public function unitPriceValue(): float
    {
        return $this->unitPriceValue;
    }

    public function lineTotalValue(): float
    {
        return $this->lineTotalValue;
    }

    public function seatCount(): int
    {
        return $this->seatCount;
    }

    public function familyTicket(): bool
    {
        return $this->familyTicket;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'quantity' => $this->quantity,
            'is_valid' => $this->isValid,
            'invalid_reason' => $this->invalidReason,
            'name' => $this->name,
            'venue' => $this->venue,
            'time' => $this->time,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'unit_price_value' => $this->unitPriceValue,
            'unit_price' => number_format($this->unitPriceValue, 2),
            'line_total_value' => $this->lineTotalValue,
            'line_total' => number_format($this->lineTotalValue, 2),
            'seat_count' => $this->seatCount,
            'familyTicket' => $this->familyTicket,
            'has_conflict' => !$this->isValid,
        ];
    }
}
