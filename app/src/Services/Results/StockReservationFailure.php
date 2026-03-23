<?php

declare(strict_types=1);

namespace App\Services\Results;

/**
 * Value object representing an out-of-stock conflict for a single event.
 */
class StockReservationFailure
{
    public function __construct(
        private int $eventId,
        private string $eventName,
        private int $requested,
        private int $available,
    ) {
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    /**
     * Convert back to the associative array structure previously returned by
     * CheckoutService::buildOutOfStockConflicts().
     */
    public function toArray(): array
    {
        return [
            'event_id'   => $this->eventId,
            'event_name' => $this->eventName,
            'requested'  => $this->requested,
            'available'  => $this->available,
        ];
    }
}

