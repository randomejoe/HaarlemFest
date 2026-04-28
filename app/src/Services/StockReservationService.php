<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckoutItem;
use App\Models\StockConflict;
use App\Models\StockReservationResult;
use App\Repositories\Interfaces\IEventRepository;
use App\Repositories\Interfaces\ITicketHoldRepository;
use App\Services\Interfaces\IStockReservationService;
use PDO;

final class StockReservationService implements IStockReservationService
{
    public function __construct(
        private IEventRepository $events,
        private ITicketHoldRepository $ticketHolds,
        private DateTimeFormatter $dateTimeFormatter,
    ) {
    }

    /**
     * @param CheckoutItem[] $checkoutItems
     */
    public function reserveStockForItems(array $checkoutItems, PDO $pdo): StockReservationResult
    {
        $failedEventIds = [];

        foreach ($checkoutItems as $item) {
            if (!$item instanceof CheckoutItem) {
                $item = CheckoutItem::fromPlannerArray((array) $item);
            }

            $reserved = $this->events->decrementTicketAmountIfAvailable($item->eventId(), $item->quantity());
            if (!$reserved) {
                $failedEventIds[] = $item->eventId();
            }
        }

        return new StockReservationResult($failedEventIds === [], array_values(array_unique($failedEventIds)));
    }

    public function restoreStockForAttempt(int $attemptId, string $reason, PDO $pdo): void
    {
        $holds = $this->ticketHolds->findByAttemptForUpdate($attemptId);
        if ($holds === []) {
            return;
        }

        $holdIds = [];
        foreach ($holds as $hold) {
            $holdIds[] = (int) ($hold['ticket_hold_id'] ?? 0);
            $this->events->incrementTicketAmount((int) ($hold['event_id'] ?? 0), (int) ($hold['quantity'] ?? 0));
        }

        $this->ticketHolds->markReleasedByIds(
            $holdIds,
            $reason,
            $this->dateTimeFormatter->currentDateTime()
        );
    }

    /**
     * @param CheckoutItem[]|array[] $items
     * @return StockConflict[]
     */
    public function getStockConflicts(array $items): array
    {
        $eventIds = array_map(static fn(array|CheckoutItem $item): int => $item instanceof CheckoutItem ? $item->eventId() : (int) ($item['event_id'] ?? 0), $items);
        $stockByEventId = $this->events->findStockByIds($eventIds);
        $conflicts = [];

        foreach ($items as $item) {
            $eventId = $item instanceof CheckoutItem ? $item->eventId() : (int) ($item['event_id'] ?? 0);
            $requested = $item instanceof CheckoutItem ? $item->quantity() : (int) ($item['quantity'] ?? 0);
            $stock = $stockByEventId[$eventId] ?? [
                'name' => (string) ($item instanceof CheckoutItem ? 'Event unavailable' : ($item['name'] ?? 'Event unavailable')),
                'ticket_amount' => 0,
            ];

            $available = (int) ($stock['ticket_amount'] ?? 0);
            if ($requested > $available) {
                $conflicts[] = new StockConflict(
                    $eventId,
                    (string) ($stock['name'] ?? 'Event unavailable'),
                    $requested,
                    $available
                );
            }
        }

        return $conflicts;
    }
}
