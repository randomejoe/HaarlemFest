<?php

namespace App\Services;

use App\Models\CheckoutItem;
use App\Models\Event;
use App\Models\User;
use App\Repositories\CheckoutRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketHoldRepository;
use PDO;
use Throwable;

class CheckoutService
{
    private const EXPIRY_CLEANUP_COOLDOWN_SECONDS = 60;
    private const EXPIRY_GRACE_PERIOD_SECONDS = 30;
    private const HOLD_DURATION_SECONDS = 600;

    private PDO $pdo;
    private PlannerService $planner;
    private EventRepository $events;
    private CheckoutRepository $checkoutAttempts;
    private TicketHoldRepository $ticketHolds;
    private PaymentGatewayStubService $paymentGateway;
    private TicketDeliveryService $ticketDelivery;

    public function __construct(
        PDO $pdo,
        PlannerService $planner,
        EventRepository $events,
        CheckoutRepository $checkoutAttempts,
        TicketHoldRepository $ticketHolds,
        PaymentGatewayStubService $paymentGateway,
        TicketDeliveryService $ticketDelivery
    ) {
        $this->pdo = $pdo;
        $this->planner = $planner;
        $this->events = $events;
        $this->checkoutAttempts = $checkoutAttempts;
        $this->ticketHolds = $ticketHolds;
        $this->paymentGateway = $paymentGateway;
        $this->ticketDelivery = $ticketDelivery;
    }

    public function releaseExpiredHolds(): array
    {
        return $this->transaction(function (): array {
            $releaseCutoff = $this->formatTimestamp($this->currentTimestamp() - self::EXPIRY_GRACE_PERIOD_SECONDS);
            $releasedAt = $this->currentDateTime();
            $releasedCount = 0;
            $expiredAttemptIds = [];

            $expiredHolds = $this->ticketHolds->findExpiredHoldsForUpdate($releaseCutoff);
            if ($expiredHolds === []) {
                return [
                    'released_count' => 0,
                    'expired_attempt_ids' => [],
                ];
            }

            $holdIds = [];
            foreach ($expiredHolds as $hold) {
                $holdIds[] = (int) $hold['ticket_hold_id'];
                $expiredAttemptIds[] = (int) $hold['checkout_attempt_id'];
                $this->events->incrementTicketAmount((int) $hold['event_id'], (int) $hold['quantity']);
                $releasedCount++;
            }

            $this->ticketHolds->markReleasedByIds($holdIds, 'expired', $releasedAt);
            $this->checkoutAttempts->markExpiredByIds($expiredAttemptIds);

            return [
                'released_count' => $releasedCount,
                'expired_attempt_ids' => array_values(array_unique($expiredAttemptIds)),
            ];
        });
    }

    public function releaseExpiredHoldsIfNeeded(bool $force = false): array
    {
        if (!$force && !$this->planner->shouldRunExpiryCleanup(self::EXPIRY_CLEANUP_COOLDOWN_SECONDS)) {
            $this->logExpiryCleanup('skipped', [
                'force' => false,
                'cooldown_seconds' => self::EXPIRY_CLEANUP_COOLDOWN_SECONDS,
            ]);

            return [
                'released_count' => 0,
                'expired_attempt_ids' => [],
                'was_executed' => false,
                'skip_reason' => 'cooldown',
            ];
        }

        $result = $this->releaseExpiredHolds();
        $this->planner->markExpiryCleanupRun();

        $response = [
            'released_count'      => $result['released_count'],
            'expired_attempt_ids' => $result['expired_attempt_ids'],
            'was_executed'        => true,
            'skip_reason'         => null,
        ];

        $this->logExpiryCleanup('executed', [
            'force' => $force,
            'released_count' => $response['released_count'],
            'expired_attempt_count' => count($response['expired_attempt_ids']),
        ]);

        return $response;
    }

    public function confirmCheckout(User $user, string $postedIdempotencyKey, bool $simulateFailure = false): array
    {
        if ($this->planner->isLocked()) {
            return [
                'status' => 'locked',
                'message' => 'Your planner is locked while payment is pending.',
                'attempt_id' => $this->planner->getLockedCheckoutAttemptId(),
            ];
        }

        if (!$this->isValidIdempotencyKey($postedIdempotencyKey)) {
            return [
                'status' => 'invalid_request',
                'message' => 'This checkout request is no longer valid. Please try again.',
            ];
        }

        $existingAttempt = $this->checkoutAttempts->findByIdempotencyKey($postedIdempotencyKey);
        if ($existingAttempt !== null) {
            return $this->resolveExistingAttempt($existingAttempt);
        }

        $checkoutPayload = $this->prepareCheckoutPayload();
        if ($checkoutPayload['error'] !== null) {
            return $checkoutPayload['error'];
        }

        $planner = $checkoutPayload['planner'];
        $items   = $checkoutPayload['items'];

        $this->planner->resetExpiryCleanupRun();

        $attemptData = $this->createPendingCheckoutAttempt($user, (float) $planner['total_price_value'], $items, $postedIdempotencyKey);
        if ($attemptData['error'] !== null) {
            return $attemptData['error'];
        }

        $attemptId = (int) ($attemptData['attempt_id'] ?? 0);
        $handoff = $this->paymentGateway->createTransaction([
            'checkout_attempt_id' => $attemptId,
            'user_id' => $user->id(),
            'planner_token' => $this->planner->getPlannerToken(),
            'amount' => (float) $planner['total_price_value'],
            'currency' => 'EUR',
        ], $simulateFailure);

        if (!(bool) ($handoff['success'] ?? false)) {
            return $this->handleFailedHandoff($attemptId, $handoff);
        }

        $this->markAttemptAsHandedOff($attemptId, $handoff);
        $this->planner->lock($attemptId);
        $this->planner->rotateIdempotencyKey();

        return [
            'status' => 'handoff_created',
            'attempt_id' => $attemptId,
            'redirect_url' => (string) ($handoff['redirect_url'] ?? '/checkout/pending/' . $attemptId),
            'provider_reference' => (string) ($handoff['provider_reference'] ?? ''),
            'message' => 'Payment handoff created. Continue to pending payment status.',
        ];
    }

    public function confirmPendingPayment(int $checkoutAttemptId, User $user): array
    {
        $userId = $user->id();

        if ($checkoutAttemptId <= 0) {
            return [
                'status' => 'not_found',
                'message' => 'Checkout attempt not found.',
            ];
        }

        if ($userId <= 0) {
            return [
                'status' => 'forbidden',
                'message' => 'You are not allowed to confirm this payment.',
            ];
        }

        $paymentResult = $this->transaction(function () use ($checkoutAttemptId, $userId): array {
            $attempt = $this->checkoutAttempts->findByIdForUpdate($checkoutAttemptId);
            if ($attempt === null) {
                return [
                    'status' => 'not_found',
                    'message' => 'Checkout attempt not found.',
                ];
            }

            if ((int) ($attempt['user_id'] ?? 0) !== $userId) {
                return [
                    'status' => 'forbidden',
                    'message' => 'You are not allowed to confirm this payment.',
                ];
            }

            $attempt = (array) $attempt;
            $resolvedStatus = $this->resolvePendingAttemptForConfirmation($checkoutAttemptId, $attempt);
            if ($resolvedStatus !== null) {
                return $resolvedStatus;
            }

            $invoiceId = $this->checkoutAttempts->createInvoice($userId, (float) ($attempt['total_price'] ?? 0));
            $createdTickets = $this->checkoutAttempts->createTicketsForAttempt($checkoutAttemptId, $userId, $invoiceId);
            $this->ticketHolds->markPaidByAttemptId($checkoutAttemptId, $this->currentDateTime());
            $this->checkoutAttempts->markPaid($checkoutAttemptId);

            return [
                'status' => 'paid',
                'attempt' => $attempt,
                'invoice_id' => $invoiceId,
                'created_tickets' => $createdTickets,
            ];
        });

        if ((string) ($paymentResult['status'] ?? '') !== 'paid') {
            return $paymentResult;
        }

        $attempt = (array) ($paymentResult['attempt'] ?? []);
        $invoiceId = (int) ($paymentResult['invoice_id'] ?? 0);
        $createdTickets = (array) ($paymentResult['created_tickets'] ?? []);

        $ticketsForDelivery = $this->buildDeliverableTickets($createdTickets);
        $invoiceForDelivery = $this->buildInvoiceForDelivery($invoiceId, $attempt, $ticketsForDelivery);
        $warnings = [];

        try {
            $this->ticketDelivery->sendPurchasedTicketsEmail($user, $attempt, $ticketsForDelivery);
        } catch (Throwable $emailError) {
            error_log('Ticket email delivery failed: ' . $emailError->getMessage());
            $warnings[] = 'ticket email delivery failed';
        }

        try {
            $this->ticketDelivery->sendInvoiceEmail($user, $attempt, $invoiceForDelivery);
        } catch (Throwable $emailError) {
            error_log('Invoice email delivery failed: ' . $emailError->getMessage());
            $warnings[] = 'invoice email delivery failed';
        }

        $warningMessage = null;
        if ($warnings !== []) {
            $warningMessage = 'Payment confirmed, but ' . implode(' and ', $warnings) . '.';
        }

        return [
            'status' => 'paid',
            'invoice_id' => $invoiceId,
            'email_warning' => $warningMessage,
            'message' => $warningMessage ?? 'Payment confirmed. Ticket and invoice PDFs were sent by email.',
        ];
    }

    private function prepareCheckoutPayload(): array
    {
        $planner = $this->planner->getDetailedPlanner();

        if ((bool) ($planner['is_empty'] ?? false)) {
            return [
                'planner' => $planner,
                'items' => [],
                'error' => [
                    'status' => 'planner_empty',
                    'message' => 'Your planner is empty.',
                ],
            ];
        }

        if ((bool) ($planner['has_invalid_items'] ?? false)) {
            return [
                'planner' => $planner,
                'items' => [],
                'error' => [
                    'status' => 'planner_invalid',
                    'message' => 'Remove unavailable events from your planner before checkout.',
                ],
            ];
        }

        $items = $this->extractValidPlannerItems($planner);

        if ($items === []) {
            return [
                'planner' => $planner,
                'items' => [],
                'error' => [
                    'status' => 'planner_invalid',
                    'message' => 'No valid planner items were found.',
                ],
            ];
        }

        return [
            'planner' => $planner,
            'items' => $items,
            'error' => null,
        ];
    }

    /**
     * Return only the valid planner-item arrays, sorted by event_id for stable DB ordering.
     *
     * @param  array $planner  Return value of PlannerService::getDetailedPlanner()
     * @return array[]
     */
    private function extractValidPlannerItems(array $planner): array
    {
        $items = array_values(array_filter(
            (array) ($planner['items'] ?? []),
            static fn(array $item): bool => (bool) ($item['is_valid'] ?? false)
        ));

        usort($items, static fn(array $a, array $b): int => $a['event_id'] <=> $b['event_id']);

        return $items;
    }

    private function isValidIdempotencyKey(string $postedIdempotencyKey): bool
    {
        if ($postedIdempotencyKey === '') {
            return false;
        }

        return hash_equals($this->planner->getIdempotencyKey(), $postedIdempotencyKey);
    }

    private function resolveExistingAttempt(array $existingAttempt): array
    {
        $status = (string) ($existingAttempt['status'] ?? '');

        if ($status === 'handoff_created') {
            $attemptId = (int) ($existingAttempt['checkout_attempt_id'] ?? 0);
            $this->planner->lock($attemptId);

            return [
                'status' => 'already_pending',
                'attempt_id' => $attemptId,
                'redirect_url' => '/checkout/pending/' . $attemptId,
                'message' => 'Payment is already pending for this checkout.',
            ];
        }

        if ($status === 'handoff_failed' || $status === 'expired') {
            $this->planner->rotateIdempotencyKey();

            return [
                'status' => 'retry_required',
                'message' => 'Please retry checkout. The previous attempt could not be completed.',
            ];
        }

        if ($status === 'paid') {
            return [
                'status' => 'already_paid',
                'attempt_id' => (int) ($existingAttempt['checkout_attempt_id'] ?? 0),
                'message' => 'This checkout attempt was already confirmed as paid.',
            ];
        }

        return [
            'status' => 'already_processing',
            'message' => 'Checkout is already being processed. Please wait.',
        ];
    }

    private function createPendingCheckoutAttempt(User $user, float $totalPrice, array $items, string $postedIdempotencyKey): array
    {
        return $this->transaction(function () use ($user, $totalPrice, $items, $postedIdempotencyKey): array {
            // Convert planner arrays to typed objects for stock reservation and persistence.
            $checkoutItems = $this->toCheckoutItems($items);

            $failedEventIds = $this->reserveStockForItems($checkoutItems);
            if ($failedEventIds !== []) {
                $this->pdo->rollBack();

                return [
                    'attempt_id' => 0,
                    'error' => [
                        'status' => 'out_of_stock',
                        'message' => 'Some items are no longer available in the requested quantities.',
                        // Original planner arrays are still needed here for event names.
                        'conflicts' => $this->buildOutOfStockConflicts($items),
                    ],
                ];
            }

            $expiresAt = $this->formatTimestamp($this->currentTimestamp() + self::HOLD_DURATION_SECONDS);
            $attemptId = $this->checkoutAttempts->createAttempt([
                'user_id'          => $user->id(),
                'planner_token'    => $this->planner->getPlannerToken(),
                'status'           => 'initiated',
                'total_price'      => $totalPrice,
                'currency'         => 'EUR',
                'hold_expires_at'  => $expiresAt,
                'idempotency_key'  => $postedIdempotencyKey,
                'payment_provider' => null,
                'payment_reference' => null,
                'error_code'       => null,
                'error_message'    => null,
            ]);

            // Repositories expect plain arrays; convert once here at the boundary.
            $attemptItemArrays = array_map(static fn(CheckoutItem $ci): array => $ci->toArray(), $checkoutItems);
            $this->checkoutAttempts->createAttemptItems($attemptId, $attemptItemArrays);
            $this->ticketHolds->createHolds(
                $attemptId,
                $user->id(),
                $this->planner->getPlannerToken(),
                $attemptItemArrays,
                $expiresAt
            );

            return [
                'attempt_id' => $attemptId,
                'error' => null,
            ];
        });
    }

    /**
     * @param  CheckoutItem[] $checkoutItems
     * @return int[]  IDs of events whose stock could not be reserved
     */
    private function reserveStockForItems(array $checkoutItems): array
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

    /**
     * Convert the flat planner-item arrays from getDetailedPlanner() into
     * typed CheckoutItem objects for the rest of the checkout flow.
     *
     * @param  array[]         $items  Planner-item arrays (PlannerItem::toArray() shape)
     * @return CheckoutItem[]
     */
    private function toCheckoutItems(array $items): array
    {
        return array_map(
            static fn(array $item): CheckoutItem => CheckoutItem::fromPlannerArray($item),
            $items
        );
    }

    private function handleFailedHandoff(int $attemptId, array $handoff): array
    {
        $this->transaction(function () use ($attemptId, $handoff): void {
            $this->releaseAttemptHoldsAndRestoreStock($attemptId, 'handoff_failed');
            $this->checkoutAttempts->markHandoffFailed(
                $attemptId,
                (string) ($handoff['error_code'] ?? 'HANDOFF_FAILED'),
                (string) ($handoff['error_message'] ?? 'Payment provider handoff failed.')
            );
        });

        $this->planner->unlock();
        $this->planner->rotateIdempotencyKey();

        return [
            'status' => 'handoff_failed',
            'attempt_id' => $attemptId,
            'message' => (string) ($handoff['error_message'] ?? 'Payment provider handoff failed. Please try again.'),
        ];
    }

    private function markAttemptAsHandedOff(int $attemptId, array $handoff): void
    {
        $this->transaction(function () use ($attemptId, $handoff): void {
            $this->ticketHolds->markTransferredByAttemptId($attemptId);
            $this->checkoutAttempts->markHandoffCreated(
                $attemptId,
                'stub_provider',
                (string) ($handoff['provider_reference'] ?? '')
            );
        });
    }

    private function resolvePendingAttemptForConfirmation(int $checkoutAttemptId, array $attempt): ?array
    {
        $status = (string) ($attempt['status'] ?? '');

        if ($status === 'paid') {
            return [
                'status' => 'already_paid',
                'message' => 'Payment was already confirmed for this attempt.',
            ];
        }

        if ($status === 'expired') {
            return [
                'status' => 'expired',
                'message' => 'This hold has already expired.',
            ];
        }

        if ($status === 'handoff_failed') {
            return [
                'status' => 'failed',
                'message' => 'This checkout handoff failed and cannot be confirmed.',
            ];
        }

        if ($status !== 'handoff_created') {
            return [
                'status' => 'invalid_status',
                'message' => 'This checkout attempt is not waiting for payment confirmation.',
            ];
        }

        $holdExpiresAt = (string) ($attempt['hold_expires_at'] ?? '');
        if ($this->isHoldPastGracePeriod($holdExpiresAt)) {
            $this->releaseAttemptHoldsAndRestoreStock($checkoutAttemptId, 'expired');
            $this->checkoutAttempts->markExpiredByIds([$checkoutAttemptId]);

            return [
                'status' => 'expired',
                'message' => 'This hold expired before payment confirmation.',
            ];
        }

        return null;
    }

    private function buildDeliverableTickets(array $createdTickets): array
    {
        if ($createdTickets === []) {
            return [];
        }

        $eventIds = array_values(array_unique(array_filter(
            array_map('intval', array_column($createdTickets, 'event_id')),
            static fn(int $id): bool => $id > 0
        )));
        $eventsById = $this->events->findByIds($eventIds);

        $deliverable = [];
        foreach ($createdTickets as $ticket) {
            $eventId = (int) ($ticket['event_id'] ?? 0);
            $deliverable[] = $this->buildDeliverableTicket($ticket, $eventsById[$eventId] ?? null);
        }

        return $deliverable;
    }

    private function buildDeliverableTicket(array $ticket, ?Event $event): array
    {
        $eventId = (int) ($ticket['event_id'] ?? 0);
        $ticketPriceValue = (float) ($ticket['ticket_price'] ?? 0);

        if ($event === null) {
            return [
                'ticket_id' => (int) ($ticket['ticket_id'] ?? 0),
                'event_id' => $eventId > 0 ? $eventId : null,
                'verification_code' => (string) ($ticket['verification_code'] ?? ''),
                'event_name' => 'Event unavailable',
                'event_date' => '-',
                'event_time' => '-',
                'venue' => 'Unknown venue',
                'ticket_price_value' => $ticketPriceValue,
                'ticket_price' => number_format($ticketPriceValue, 2),
            ];
        }

        return [
            'ticket_id' => (int) ($ticket['ticket_id'] ?? 0),
            'event_id' => $eventId,
            'verification_code' => (string) ($ticket['verification_code'] ?? ''),
            'event_name' => $event->name(),
            'event_date' => $event->startsAt()->format('D j M Y'),
            'event_time' => $event->formattedTimeRange(),
            'venue' => $event->venue(),
            'ticket_price_value' => $ticketPriceValue,
            'ticket_price' => number_format($ticketPriceValue, 2),
        ];
    }

    private function buildInvoiceForDelivery(int $invoiceId, array $attempt, array $tickets): array
    {
        $linesByKey = [];

        foreach ($tickets as $ticket) {
            $eventKey = (string) ($ticket['event_id'] ?? 'unknown');
            $priceKey = number_format((float) ($ticket['ticket_price_value'] ?? 0), 2, '.', '');
            $key = $eventKey . '|' . $priceKey;

            if (!isset($linesByKey[$key])) {
                $linesByKey[$key] = [
                    'event_name' => (string) ($ticket['event_name'] ?? 'Event'),
                    'event_date' => (string) ($ticket['event_date'] ?? '-'),
                    'event_time' => (string) ($ticket['event_time'] ?? '-'),
                    'venue' => (string) ($ticket['venue'] ?? 'Venue to be announced'),
                    'quantity' => 0,
                    'unit_price_value' => (float) ($ticket['ticket_price_value'] ?? 0),
                    'line_total_value' => 0.0,
                ];
            }

            $linesByKey[$key]['quantity']++;
            $linesByKey[$key]['line_total_value'] += (float) ($ticket['ticket_price_value'] ?? 0);
        }

        return [
            'invoice_id' => $invoiceId,
            'invoice_number' => 'INV-' . $invoiceId,
            'issued_at' => $this->currentDateTime(),
            'currency' => (string) ($attempt['currency'] ?? 'EUR'),
            'total_price_value' => (float) ($attempt['total_price'] ?? 0),
            'total_tickets' => count($tickets),
            'items' => array_values($linesByKey),
        ];
    }

    private function releaseAttemptHoldsAndRestoreStock(int $checkoutAttemptId, string $reason): void
    {
        $holds = $this->ticketHolds->findByAttemptForUpdate($checkoutAttemptId);
        if ($holds === []) {
            return;
        }

        $holdIds = [];
        foreach ($holds as $hold) {
            $holdIds[] = (int) $hold['ticket_hold_id'];
            $this->events->incrementTicketAmount((int) $hold['event_id'], (int) $hold['quantity']);
        }

        $this->ticketHolds->markReleasedByIds($holdIds, $reason, $this->currentDateTime());
    }

    protected function currentTimestamp(): int
    {
        return time();
    }

    protected function currentDateTime(): string
    {
        return $this->formatTimestamp($this->currentTimestamp());
    }

    private function formatTimestamp(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function isHoldPastGracePeriod(string $holdExpiresAt): bool
    {
        if ($holdExpiresAt === '') {
            return false;
        }

        $expiryTimestamp = strtotime($holdExpiresAt);
        if ($expiryTimestamp === false) {
            return false;
        }

        return ($expiryTimestamp + self::EXPIRY_GRACE_PERIOD_SECONDS) <= $this->currentTimestamp();
    }

    private function buildOutOfStockConflicts(array $items): array
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
                $conflicts[] = [
                    'event_id' => $eventId,
                    'event_name' => (string) ($stock['name'] ?? 'Event unavailable'),
                    'requested' => $requested,
                    'available' => $available,
                ];
            }
        }

        return $conflicts;
    }

    private function logExpiryCleanup(string $event, array $context): void
    {
        $parts = [];
        foreach ($context as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $parts[] = $key . '=' . $value;
        }

        error_log('expiry_cleanup ' . $event . ' ' . implode(' ', $parts));
    }

    private function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $operation();

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
