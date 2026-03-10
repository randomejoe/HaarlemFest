<?php

namespace App\Services;

use App\Repositories\EventRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class PlannerService
{
    private const SESSION_KEY = 'planner';
    private const TOKEN_KEY = 'planner_token';
    private const FLASH_KEY = 'planner_flash';
    private const EXPIRY_CLEANUP_KEY = 'planner_expiry_cleanup';

    private EventRepository $events;

    public function __construct(EventRepository $events)
    {
        $this->events = $events;
        $this->ensureInitialized();
    }

    public function getPlannerToken(): string
    {
        $this->ensureInitialized();
        return (string) $_SESSION[self::TOKEN_KEY];
    }

    public function getItems(): array
    {
        $planner = $this->getPlanner();
        $items = (array) ($planner['items'] ?? []);
        $filteredItems = $this->filterOutFreeItems($items);

        if ($filteredItems !== $items) {
            $planner['items'] = $filteredItems;
            $this->touchAndPersistPlanner($planner);
        }

        return $filteredItems;
    }

    public function isLocked(): bool
    {
        return $this->getLockedCheckoutAttemptId() !== null;
    }

    public function getLockedCheckoutAttemptId(): ?int
    {
        $planner = $this->getPlanner();
        $attemptId = $planner['locked_checkout_attempt_id'];

        if ($attemptId === null) {
            return null;
        }

        return (int) $attemptId > 0 ? (int) $attemptId : null;
    }

    public function lock(int $checkoutAttemptId): void
    {
        $this->assertCheckoutAttemptId($checkoutAttemptId);

        $planner = $this->getPlanner();
        $planner['locked_checkout_attempt_id'] = $checkoutAttemptId;
        $this->touchAndPersistPlanner($planner);
    }

    public function unlock(): void
    {
        $planner = $this->getPlanner();
        $planner['locked_checkout_attempt_id'] = null;
        $this->touchAndPersistPlanner($planner);
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

        $this->assertEventCanBePlanned($eventId);

        $planner = $this->getPlanner();
        $current = (int) ($planner['items'][$eventId] ?? 0);
        $planner['items'][$eventId] = $current + $quantity;
        $this->touchAndPersistPlanner($planner);
    }

    public function updateItemQuantity(int $eventId, int $quantity): void
    {
        $this->assertEventId($eventId);
        $this->assertQuantity($quantity);
        $this->assertUnlocked();

        $planner = $this->getPlanner();

        if (!isset($planner['items'][$eventId])) {
            throw new RuntimeException('This event is not in your planner.');
        }

        $this->assertEventCanBePlanned($eventId);

        $planner['items'][$eventId] = $quantity;
        $this->touchAndPersistPlanner($planner);
    }

    public function removeItem(int $eventId): void
    {
        $this->assertEventId($eventId);
        $this->assertUnlocked();

        $planner = $this->getPlanner();
        unset($planner['items'][$eventId]);
        $this->touchAndPersistPlanner($planner);
    }

    public function clear(): void
    {
        $this->assertUnlocked();

        $planner = $this->getPlanner();
        $planner['items'] = [];
        $this->touchAndPersistPlanner($planner);
    }

    public function getIdempotencyKey(): string
    {
        $planner = $this->getPlanner();
        return (string) $planner['idempotency_key'];
    }

    public function rotateIdempotencyKey(): string
    {
        $planner = $this->getPlanner();
        $planner['idempotency_key'] = $this->generateToken();
        $this->touchAndPersistPlanner($planner);
        return $planner['idempotency_key'];
    }

    public function shouldRunExpiryCleanup(int $cooldownSeconds): bool
    {
        if ($cooldownSeconds <= 0) {
            return true;
        }

        $lastRunAt = $this->getLastExpiryCleanupRunAt();
        if ($lastRunAt === null) {
            return true;
        }

        return ($lastRunAt + $cooldownSeconds) <= time();
    }

    public function markExpiryCleanupRun(?int $timestamp = null): void
    {
        $_SESSION[self::EXPIRY_CLEANUP_KEY] = [
            'last_run_at' => $timestamp ?? time(),
        ];
    }

    public function resetExpiryCleanupRun(): void
    {
        unset($_SESSION[self::EXPIRY_CLEANUP_KEY]);
    }

    public function setFlash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function consumeFlash(): ?array
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return null;
        }

        $flash = $_SESSION[self::FLASH_KEY];
        unset($_SESSION[self::FLASH_KEY]);

        return [
            'type' => (string) ($flash['type'] ?? 'info'),
            'message' => (string) ($flash['message'] ?? ''),
        ];
    }

    public function getDetailedPlanner(): array
    {
        $items = $this->getItems();
        $eventIds = array_map('intval', array_keys($items));
        $eventsById = $this->events->findByIds($eventIds);
        $summary = $this->summarizePlannerItems($items, $eventsById);
        $lockedCheckoutAttemptId = $this->getLockedCheckoutAttemptId();

        return [
            'items' => $summary['items'],
            'items_map' => $items,
            'total_quantity' => $summary['total_quantity'],
            'total_price_value' => $summary['total_price_value'],
            'total_price' => number_format((float) $summary['total_price_value'], 2),
            'is_empty' => (int) $summary['total_quantity'] === 0,
            'has_invalid_items' => $summary['invalid_item_ids'] !== [],
            'invalid_item_ids' => $summary['invalid_item_ids'],
            'locked_checkout_attempt_id' => $lockedCheckoutAttemptId,
            'is_locked' => $lockedCheckoutAttemptId !== null,
            'idempotency_key' => $this->getIdempotencyKey(),
            'time_conflicts' => $this->detectTimeConflicts($summary['conflict_items']),
        ];
    }

    private function getPlanner(): array
    {
        $this->ensureInitialized();

        /** @var array<string, mixed> $planner */
        $planner = $_SESSION[self::SESSION_KEY];
        return $planner;
    }

    private function persistPlanner(array $planner): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'items' => $this->normalizeItems((array) ($planner['items'] ?? [])),
            'locked_checkout_attempt_id' => $planner['locked_checkout_attempt_id'] ?? null,
            'idempotency_key' => (string) ($planner['idempotency_key'] ?? $this->generateToken()),
            'updated_at' => (int) ($planner['updated_at'] ?? time()),
        ];
    }

    private function ensureInitialized(): void
    {
        if (!isset($_SESSION[self::TOKEN_KEY]) || !is_string($_SESSION[self::TOKEN_KEY]) || $_SESSION[self::TOKEN_KEY] === '') {
            $_SESSION[self::TOKEN_KEY] = $this->generateToken();
        }

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = $this->defaultPlanner();
            return;
        }

        $planner = $_SESSION[self::SESSION_KEY];
        $planner['items'] = $this->normalizeItems((array) ($planner['items'] ?? []));

        if (!array_key_exists('locked_checkout_attempt_id', $planner)) {
            $planner['locked_checkout_attempt_id'] = null;
        }

        if (!isset($planner['idempotency_key']) || !is_string($planner['idempotency_key']) || $planner['idempotency_key'] === '') {
            $planner['idempotency_key'] = $this->generateToken();
        }

        $planner['updated_at'] = (int) ($planner['updated_at'] ?? time());

        $_SESSION[self::SESSION_KEY] = $planner;
    }

    private function defaultPlanner(): array
    {
        return [
            'items' => [],
            'locked_checkout_attempt_id' => null,
            'idempotency_key' => $this->generateToken(),
            'updated_at' => time(),
        ];
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function getLastExpiryCleanupRunAt(): ?int
    {
        $state = $_SESSION[self::EXPIRY_CLEANUP_KEY] ?? null;
        if (!is_array($state)) {
            return null;
        }

        $lastRunAt = (int) ($state['last_run_at'] ?? 0);
        return $lastRunAt > 0 ? $lastRunAt : null;
    }

    private function touchAndPersistPlanner(array $planner): void
    {
        $planner['updated_at'] = time();
        $this->persistPlanner($planner);
    }

    private function summarizePlannerItems(array $items, array $eventsById): array
    {
        $summary = [
            'items' => [],
            'invalid_item_ids' => [],
            'total_quantity' => 0,
            'total_price_value' => 0.0,
            'conflict_items' => [],
        ];

        foreach ($items as $eventIdRaw => $quantityRaw) {
            $eventId = (int) $eventIdRaw;
            $quantity = max(0, (int) $quantityRaw);

            if ($eventId <= 0 || $quantity <= 0) {
                continue;
            }

            $summary['total_quantity'] += $quantity;

            $event = $eventsById[$eventId] ?? null;
            if ($event === null) {
                $summary['invalid_item_ids'][] = $eventId;
                $summary['items'][] = $this->buildUnavailablePlannerItem($eventId, $quantity);
                continue;
            }

            $plannerItem = $this->buildPlannerItem($eventId, $quantity, $event);
            $summary['items'][] = $plannerItem;
            $summary['total_price_value'] += (float) $plannerItem['line_total_value'];
            $summary['conflict_items'][] = $this->buildConflictItem($eventId, $event);
        }

        return $summary;
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

            if ($event !== null && $this->isFreeEvent($event)) {
                continue;
            }

            $filtered[$eventId] = (int) $quantityRaw;
        }

        return $filtered;
    }

    private function assertEventCanBePlanned(int $eventId): void
    {
        $event = $this->events->findById($eventId);
        if ($event === null) {
            throw new RuntimeException('This event is no longer available.');
        }

        if ($this->isFreeEvent($event)) {
            throw new RuntimeException('Free events are not added to the planner.');
        }

        if ($event['ticket_amount'] !== null && (int) $event['ticket_amount'] <= 0) {
            throw new RuntimeException('This event is sold out.');
        }
    }

    private function isFreeEvent(array $event): bool
    {
        return (float) ($event['ticket_price'] ?? 0) <= 0.0;
    }

    private function buildUnavailablePlannerItem(int $eventId, int $quantity): array
    {
        return [
            'event_id' => $eventId,
            'quantity' => $quantity,
            'is_valid' => false,
            'invalid_reason' => 'This event is no longer available.',
            'name' => 'Event unavailable',
            'venue' => 'Unknown venue',
            'time' => 'Unknown time',
            'unit_price_value' => 0.0,
            'unit_price' => number_format(0, 2),
            'line_total_value' => 0.0,
            'line_total' => number_format(0, 2),
            'seat_count' => 0,
        ];
    }

    private function buildPlannerItem(int $eventId, int $quantity, array $event): array
    {
        $startsAt = new DateTimeImmutable((string) $event['start_time']);
        $endsAt = new DateTimeImmutable((string) $event['end_time']);
        $unitPrice = isset($event['ticket_price']) ? (float) $event['ticket_price'] : 0.0;
        $lineTotal = $unitPrice * $quantity;

        return [
            'event_id' => $eventId,
            'quantity' => $quantity,
            'is_valid' => true,
            'invalid_reason' => null,
            'name' => (string) $event['name'],
            'venue' => (string) ($event['venue_location'] ?? 'Venue to be announced'),
            'time' => $this->formatPlannerTime($startsAt, $endsAt),
            'start_time' => (string) $event['start_time'],
            'end_time' => (string) $event['end_time'],
            'unit_price_value' => $unitPrice,
            'unit_price' => number_format($unitPrice, 2),
            'line_total_value' => $lineTotal,
            'line_total' => number_format($lineTotal, 2),
            'seat_count' => max(0, (int) ($event['ticket_amount'] ?? 0)),
        ];
    }

    private function buildConflictItem(int $eventId, array $event): array
    {
        return [
            'event_id' => $eventId,
            'name' => (string) $event['name'],
            'start_time' => (string) $event['start_time'],
            'end_time' => (string) $event['end_time'],
        ];
    }

    private function formatPlannerTime(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): string
    {
        return $startsAt->format('D j M H:i') . ' - ' . $endsAt->format('H:i');
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
