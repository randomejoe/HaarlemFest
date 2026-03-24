<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PlannerItem;
use App\Models\PlannerSummary;
use App\Repositories\EventRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class PlannerService
{
    private const LOCK_HOLD_DURATION_SECONDS = 600;

    private EventRepository $events;
    private SessionManager $session;

    public function __construct(EventRepository $events, SessionManager $session)
    {
        $this->events = $events;
        $this->session = $session;
    }

    public function getPlannerToken(): string
    {
        return $this->session->getPlannerToken();
    }

    public function getItems(): array
    {
        $planner = $this->session->getPlannerState();
        $items = (array) ($planner['items'] ?? []);
        $filteredItems = $this->filterOutFreeItems($items);

        if ($filteredItems !== $items) {
            $planner['items'] = $filteredItems;
            $this->session->setPlannerState($planner);
        }

        return $filteredItems;
    }

    public function isLocked(): bool
    {
        return $this->getLockedCheckoutAttemptId() !== null;
    }

    public function getLockedCheckoutAttemptId(): ?int
    {
        $planner = $this->session->getPlannerState();
        $attemptId = $planner['locked_checkout_attempt_id'];

        if ($attemptId === null) {
            return null;
        }

        $attemptIdInt = (int) $attemptId;
        if ($attemptIdInt <= 0) {
            return null;
        }

        $expiresAtUnix = $this->getLockedCheckoutExpiresAtUnix($planner);
        if ($expiresAtUnix !== null && $expiresAtUnix <= time()) {
            $this->unlock();
            return null;
        }

        return $attemptIdInt;
    }

    public function lock(int $checkoutAttemptId, ?string $holdExpiresAt = null): void
    {
        $this->assertCheckoutAttemptId($checkoutAttemptId);

        $planner = $this->session->getPlannerState();
        $planner['locked_checkout_attempt_id'] = $checkoutAttemptId;
        $planner['locked_checkout_expires_at'] = $this->computeLockExpiresAtUnix($holdExpiresAt);
        $this->touchAndPersistPlanner($planner);
    }

    public function unlock(): void
    {
        $planner = $this->session->getPlannerState();
        $planner['locked_checkout_attempt_id'] = null;
        $planner['locked_checkout_expires_at'] = null;
        $this->touchAndPersistPlanner($planner);
    }

    public function unlockIfAttemptId(int $checkoutAttemptId): bool
    {
        $lockedAttemptId = $this->getLockedCheckoutAttemptId();
        if ($lockedAttemptId === null) {
            return false;
        }

        if ($lockedAttemptId !== $checkoutAttemptId) {
            return false;
        }

        $this->unlock();
        return true;
    }

    public function unlockIfExpired(array $expiredAttemptIds): bool
    {
        $attemptId = $this->getLockedCheckoutAttemptId();
        if ($attemptId === null) {
            return false;
        }

        $ids = array_values(array_unique(array_map('intval', $expiredAttemptIds)));
        if (!in_array($attemptId, $ids, true)) {
            return false;
        }

        $this->unlock();
        return true;
    }

    public function addItem(int $eventId, int $quantity): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);
        $this->assertUnlocked();

        $event = $this->assertEventCanBePlanned($eventId);

        $planner = $this->session->getPlannerState();
        $current = (int) ($planner['items'][$eventId] ?? 0);
        $requestedQuantity = $current + $quantity;
        $this->assertQuantityWithinAvailability($event, $requestedQuantity);

        $planner['items'][$eventId] = $current + $quantity;
        $this->touchAndPersistPlanner($planner);
    }

    public function updateItemQuantity(int $eventId, int $quantity): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);
        $this->assertUnlocked();

        $planner = $this->session->getPlannerState();

        if (!isset($planner['items'][$eventId])) {
            throw new RuntimeException('This event is not in your planner.');
        }

        $event = $this->assertEventCanBePlanned($eventId);
        $this->assertQuantityWithinAvailability($event, $quantity);

        $planner['items'][$eventId] = $quantity;
        $this->touchAndPersistPlanner($planner);
    }

    public function removeItem(int $eventId): void
    {
        $this->assertEventId($eventId);
        $this->assertUnlocked();

        $planner = $this->session->getPlannerState();
        unset($planner['items'][$eventId]);
        $this->touchAndPersistPlanner($planner);
    }

    public function clear(): void
    {
        $this->assertUnlocked();

        $planner = $this->session->getPlannerState();
        $planner['items'] = [];
        $this->touchAndPersistPlanner($planner);
    }

    public function getIdempotencyKey(): string
    {
        $planner = $this->session->getPlannerState();
        return (string) $planner['idempotency_key'];
    }

    public function rotateIdempotencyKey(): string
    {
        $planner = $this->session->getPlannerState();
        $planner['idempotency_key'] = $this->session->generateToken();
        $this->touchAndPersistPlanner($planner);
        return $planner['idempotency_key'];
    }

    public function shouldRunExpiryCleanup(int $cooldownSeconds): bool
    {
        return $this->session->shouldRunExpiryCleanup($cooldownSeconds);
    }

    public function markExpiryCleanupRun(?int $timestamp = null): void
    {
        $this->session->markExpiryCleanupRun($timestamp);
    }

    public function resetExpiryCleanupRun(): void
    {
        $this->session->resetExpiryCleanupRun();
    }

    public function setFlash(string $type, string $message): void
    {
        $this->session->setFlash($type, $message);
    }

    public function consumeFlash(): ?array
    {
        return $this->session->consumeFlash();
    }

    public function getDetailedPlanner(): array
    {
        $items = $this->getItems();
        $eventIds = array_map('intval', array_keys($items));
        $eventsById = $this->events->findByIds($eventIds);
        $summary = PlannerSummary::fromRawItems($items, $eventsById);
        $lockedCheckoutAttemptId = $this->getLockedCheckoutAttemptId();

        // Convert PlannerItem objects to plain arrays so that views and
        // downstream services (CheckoutService) continue to receive the same
        // flat array shape they already rely on.
        $itemArrays = array_map(
            static fn(PlannerItem $p): array => $p->toArray(),
            $summary->plannerItems()
        );

        return [
            'items' => $itemArrays,
            'items_map' => $items,
            'total_quantity' => $summary->totalQuantity(),
            'total_price_value' => $summary->totalPriceValue(),
            'total_price' => $summary->formattedTotalPrice(),
            'is_empty' => $summary->isEmpty(),
            'has_invalid_items' => $summary->hasInvalidItems(),
            'invalid_item_ids' => $summary->invalidItemIds(),
            'locked_checkout_attempt_id' => $lockedCheckoutAttemptId,
            'is_locked' => $lockedCheckoutAttemptId !== null,
            'idempotency_key' => $this->getIdempotencyKey(),
            'time_conflicts' => $this->detectTimeConflicts($summary->conflictItems()),
        ];
    }

    private function touchAndPersistPlanner(array $planner): void
    {
        $planner['updated_at'] = time();
        $this->session->setPlannerState($planner);
    }

    private function computeLockExpiresAtUnix(?string $holdExpiresAt): int
    {
        if ($holdExpiresAt !== null) {
            $holdExpiresAt = trim($holdExpiresAt);
        }

        if ($holdExpiresAt !== null && $holdExpiresAt !== '') {
            $expiryTimestamp = strtotime($holdExpiresAt);
            if ($expiryTimestamp !== false && $expiryTimestamp > 0) {
                return $expiryTimestamp;
            }
        }

        // Fallback: if we don't have the checkout hold expiry, approximate using
        // the hold duration used elsewhere in the checkout flow.
        return time() + self::LOCK_HOLD_DURATION_SECONDS;
    }

    private function getLockedCheckoutExpiresAtUnix(array $planner): ?int
    {
        $expiresAt = $planner['locked_checkout_expires_at'] ?? null;
        if ($expiresAt === null) {
            return null;
        }

        if (is_int($expiresAt)) {
            return $expiresAt > 0 ? $expiresAt : null;
        }

        if (is_string($expiresAt) && $expiresAt !== '' && ctype_digit($expiresAt)) {
            $expiresAtInt = (int) $expiresAt;
            return $expiresAtInt > 0 ? $expiresAtInt : null;
        }

        // Fallback: if a legacy planner lock doesn't have `locked_checkout_expires_at`,
        // derive an expiry timestamp based on the last planner touch time.
        if (!isset($planner['updated_at'])) {
            return null;
        }

        $updatedAt = (int) $planner['updated_at'];
        if ($updatedAt <= 0) {
            return null;
        }

        return $updatedAt + self::LOCK_HOLD_DURATION_SECONDS;
    }

    private function assertCheckoutAttemptId(int $checkoutAttemptId): void
    {
        if ($checkoutAttemptId <= 0) {
            throw new InvalidArgumentException('Invalid checkout attempt id.');
        }
    }

    private function assertEventId(int $eventId): void
    {
        if ($eventId <= 0) {
            throw new InvalidArgumentException('Invalid event selection.');
        }
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }

    private function assertUnlocked(): void
    {
        if ($this->isLocked()) {
            throw new RuntimeException('Your planner is locked while payment is pending.');
        }
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $eventIdRaw => $quantityRaw) {
            $eventId = (int) $eventIdRaw;
            $quantity = (int) $quantityRaw;

            if ($eventId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[$eventId] = $quantity;
        }

        return $normalized;
    }

    private function filterOutFreeItems(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $eventIds = array_map('intval', array_keys($items));
        $eventsById = $this->events->findByIds($eventIds);
        $filtered = [];

        foreach ($items as $eventIdRaw => $quantityRaw) {
            $eventId = (int) $eventIdRaw;
            $event = $eventsById[$eventId] ?? null;

            if ($event !== null && $event->isFree()) {
                continue;
            }

            $filtered[$eventId] = (int) $quantityRaw;
        }

        return $filtered;
    }

    private function assertEventCanBePlanned(int $eventId): Event
    {
        $event = $this->events->findById($eventId);
        if ($event === null) {
            throw new RuntimeException('This event is no longer available.');
        }

        if ($event->isFree()) {
            throw new RuntimeException('Free events are not added to the planner.');
        }

        if ($event->isSoldOut()) {
            throw new RuntimeException('This event is sold out.');
        }

        return $event;
    }

    private function assertQuantityWithinAvailability(Event $event, int $requestedQuantity): void
    {
        if (!$event->hasTrackedStock()) {
            return;
        }

        $availableSeats = $event->seatCount() ?? 0;
        if ($requestedQuantity <= $availableSeats) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Only %d %s available for this event.',
            $availableSeats,
            $availableSeats === 1 ? 'seat is' : 'seats are'
        ));
    }

    private function detectTimeConflicts(array $items): array
    {
        $conflicts = [];
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            $leftStart = new DateTimeImmutable((string) $items[$i]['start_time']);
            $leftEnd = new DateTimeImmutable((string) $items[$i]['end_time']);

            for ($j = $i + 1; $j < $count; $j++) {
                $rightStart = new DateTimeImmutable((string) $items[$j]['start_time']);
                $rightEnd = new DateTimeImmutable((string) $items[$j]['end_time']);

                if ($leftStart < $rightEnd && $rightStart < $leftEnd) {
                    $conflicts[] = sprintf(
                        '%s overlaps with %s.',
                        (string) $items[$i]['name'],
                        (string) $items[$j]['name']
                    );
                }
            }
        }

        return $conflicts;
    }
}
