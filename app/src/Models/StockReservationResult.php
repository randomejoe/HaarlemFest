<?php

namespace App\Models;

final readonly class StockReservationResult
{
    /**
     * @param int[] $failedEventIds
     */
    public function __construct(
        public bool $ok,
        public array $failedEventIds = [],
    ) {
    }

    public function isOk(): bool
    {
        return $this->ok;
    }
}
