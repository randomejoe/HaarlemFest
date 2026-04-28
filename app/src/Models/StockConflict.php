<?php

namespace App\Models;

final class StockConflict
{
    public function __construct(
        private int $eventId,
        private string $eventName,
        private int $requested,
        private int $available,
    ) {
    }

    public function eventId(): int
    {
        return $this->eventId;
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function requested(): int
    {
        return $this->requested;
    }

    public function available(): int
    {
        return $this->available;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->eventName,
            'requested' => $this->requested,
            'available' => $this->available,
        ];
    }
}
