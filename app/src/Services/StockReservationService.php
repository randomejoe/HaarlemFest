<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckoutItem;
use App\Repositories\EventRepository;
use App\Repositories\TicketHoldRepository;
use App\Services\Results\StockReservationFailure;
use PDO;

final class StockReservationService
{
    public function __construct(
        private EventRepository $events,
        private TicketHoldRepository $ticketHolds,
        private DateTimeFormatter $dateTimeFormatter,
        private PDO $pdo,
    ) {
    }

    /**
     * @param  CheckoutItem[] $checkoutItems
     * @return int[]          IDs of events whose stock could not be reserved
     */
    public function reserveStockForItems(array $checkoutItems): array
    {
        $failedEventIds = [];

        foreach ($checkoutItems as $item) {
            $reserved = $this->events->decrementTicketAmountIfAvailable($item->eventId(), $item->quantity());
            if (!$reserved) {
                $failedEventIds[] = $item->eventId();
            }
        }

        return $failedEventIds;
    }

    public function releaseAndRestoreStock(int $checkoutAttemptId, string $reason): void
    {
        $this->pdo->beginTransaction();

        try {
            $holds = $this->ticketHolds->findByAttemptForUpdate($checkoutAttemptId);
            if ($holds !== []) {
                $holdIds = [];
                foreach ($holds as $hold) {
                    $holdIds[] = (int) $hold['ticket_hold_id'];
                    $this->events->incrementTicketAmount((int) $hold['event_id'], (int) $hold['quantity']);
                }

                $this->ticketHolds->markReleasedByIds(
                    $holdIds,
                    $reason,
                    $this->dateTimeFormatter->currentDateTime()
                );
            }

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return StockReservationFailure[]
     */
    public function getStockConflicts(array $items): array
    {
        $eventIds = array_map(static fn(array $item): int => (int) $item['event_id'], $items);
        $stockByEventId = $this->events->findStockByIds($eventIds);
        $conflicts = [];

        foreach ($items as $item) {
            $eventId = (int) $item['event_id'];
            $requested = (int) $item['quantity'];
            $stock = $stockByEventId[$eventId] ?? [
                'name' => (string) ($item['name'] ?? 'Event unavailable'),
                'ticket_amount' => 0,
            ];

            $available = (int) ($stock['ticket_amount'] ?? 0);
            if ($requested > $available) {
                $conflicts[] = new StockReservationFailure(
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

