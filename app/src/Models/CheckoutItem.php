<?php

declare(strict_types=1);

namespace App\Models;

final class CheckoutItem
{
    private function __construct(
        private int $eventId,
        private int $quantity,
        private float $unitPrice,
        private float $lineTotal,
        private bool $familyTicket
    ) {
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function fromPlannerArray(array $item): self
    {
        return new self(
            eventId: (int) ($item['event_id'] ?? 0),
            quantity: (int) ($item['quantity'] ?? 0),
            unitPrice: (float) ($item['unit_price_value'] ?? $item['unit_price'] ?? 0.0),
            lineTotal: (float) ($item['line_total_value'] ?? $item['line_total'] ?? 0.0),
            familyTicket: (bool) ($item['familyTicket'] ?? $item['family_ticket'] ?? false)
        );
    }

    public static function fromPlannerItem(PlannerItem $item): self
    {
        return new self(
            eventId: $item->eventId(),
            quantity: $item->quantity(),
            unitPrice: $item->unitPriceValue(),
            lineTotal: $item->lineTotalValue(),
            familyTicket: $item->familyTicket()
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

    public function unitPrice(): float
    {
        return $this->unitPrice;
    }

    public function lineTotal(): float
    {
        return $this->lineTotal;
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
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
            'family_ticket' => $this->familyTicket,
        ];
    }
}
