<?php

namespace App\Services\Interfaces;

use App\Models\CheckoutItem;
use App\Models\StockConflict;
use App\Models\StockReservationResult;
use PDO;

interface IStockReservationService
{
    /**
     * @param CheckoutItem[] $checkoutItems
     */
    public function reserveStockForItems(array $checkoutItems, PDO $pdo): StockReservationResult;

    public function restoreStockForAttempt(int $attemptId, string $reason, PDO $pdo): void;

    /**
     * @param  array<array{event_id:int,quantity:int,name?:string}> $items
     * @return StockConflict[]
     */
    public function getStockConflicts(array $items): array;
}
