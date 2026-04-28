<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckoutAttempt;
use App\Models\CheckoutItem;
use App\Models\CheckoutResult;
use App\Models\HoldExpiryResult;
use App\Models\Invoice;
use App\Models\PaymentConfirmationResult;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Interfaces\ICheckoutRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\Interfaces\ICheckoutHoldManager;
use App\Services\Interfaces\ICheckoutService;
use App\Services\Interfaces\IPlannerService;
use App\Services\Interfaces\IPaymentHandoffService;
use App\Services\Interfaces\IStockReservationService;
use App\Services\Interfaces\ITicketDeliveryService;
use PDO;
use PDOException;
use Throwable;

final class CheckoutService implements ICheckoutService
{
    private const REQUIRED_FIELDS = ['first_name', 'last_name', 'address', 'city', 'country', 'phone_number'];
    private const CHECKOUT_PATH = '/checkout';
    private const HOLD_DURATION_SECONDS = 600;
    private const EXPIRY_CLEANUP_COOLDOWN_SECONDS = 60;

    public function __construct(
        private PDO $pdo,
        private IPlannerService $planner,
        private ICheckoutRepository $checkoutAttempts,
        private ICheckoutHoldManager $holdManager,
        private DateTimeFormatter $dateTimeFormatter,
        private IStockReservationService $stockReservation,
        private IPaymentHandoffService $handoffService,
        private ITicketDeliveryService $ticketDelivery,
        private IUserRepository $users,
    ) {
    }

    public function isPlannerLocked(): bool
    {
        return $this->planner->isLocked();
    }

    public function getLockedAttemptId(): ?int
    {
        return $this->planner->getLockedCheckoutAttemptId();
    }

    public function unlockIfAttemptId(int $attemptId): void
    {
        $this->planner->unlockIfAttemptId($attemptId);
    }

    public function clearPlannerIfUnlocked(): void
    {
        if (!$this->planner->isLocked()) {
            $this->planner->clear();
        }
    }

    public function consumeFlash(): ?array
    {
        return $this->planner->consumeFlash();
    }

    public function setFlash(string $type, string $message): void
    {
        $this->planner->setFlash($type, $message);
    }

    public function getIdempotencyKey(): string
    {
        return $this->planner->getIdempotencyKey();
    }

    public function buildCheckoutView(User $user): array
    {
        $missing = $this->missingCheckoutDetails($user);

        return [
            'planner' => $this->planner->getDetailedPlanner(),
            'user' => $user,
            'flash' => $this->planner->consumeFlash(),
            'missing_fields' => $missing,
            'requires_details' => $missing !== [],
            'idempotency_key' => $this->planner->getIdempotencyKey(),
        ];
    }

    public function buildPendingView(int $attemptId, User $user): array
    {
        return [
            'attempt' => $this->checkoutAttempts->findById($attemptId),
            'items' => $this->checkoutAttempts->findItemsWithEventData($attemptId),
            'flash' => $this->planner->consumeFlash(),
            'user' => $user,
        ];
    }

    public function loadCheckoutUser(int $userId): ?User
    {
        return $this->users->findById($userId);
    }

    public function missingCheckoutDetails(User $user): array
    {
        $missing = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            $method = match ($field) {
                'first_name' => 'firstName',
                'last_name' => 'lastName',
                'phone_number' => 'phoneNumber',
                default => $field,
            };

            if (trim((string) $user->{$method}()) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function saveCheckoutDetails(int $userId, array $details): void
    {
        $this->users->updateCheckoutDetails($userId, $details);
    }

    public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult
    {
        if (!$force && !$this->planner->shouldRunExpiryCleanup(self::EXPIRY_CLEANUP_COOLDOWN_SECONDS)) {
            return HoldExpiryResult::skipped('cooldown');
        }

        $result = $this->holdManager->releaseExpiredHoldsIfNeeded(true);
        $this->planner->markExpiryCleanupRun();

        return $result;
    }

    public function releaseExpiredHolds(): HoldExpiryResult
    {
        return $this->holdManager->releaseExpiredHolds();
    }

    public function confirmCheckout(User $user, string $postedIdempotencyKey): CheckoutResult
    {
        if ($this->planner->isLocked()) {
            return CheckoutResult::locked((int) $this->planner->getLockedCheckoutAttemptId());
        }

        if ($postedIdempotencyKey === '' || !hash_equals($this->planner->getIdempotencyKey(), $postedIdempotencyKey)) {
            return CheckoutResult::invalidRequest();
        }

        $existingAttempt = $this->checkoutAttempts->findByIdempotencyKey($postedIdempotencyKey);
        if ($existingAttempt !== null) {
            return $this->resolveExistingAttempt($existingAttempt);
        }

        $planner = $this->planner->getDetailedPlanner();
        if ((bool) ($planner['is_empty'] ?? false)) {
            return CheckoutResult::emptyPlanner();
        }

        if ((bool) ($planner['has_invalid_items'] ?? false)) {
            return CheckoutResult::invalidPlanner();
        }

        $missingFields = $this->missingCheckoutDetails($user);
        if ($missingFields !== []) {
            return new CheckoutResult(
                'details_required',
                'Please complete your required details before checkout.'
            );
        }

        $plannerItems = $this->extractValidPlannerItems($planner);
        if ($plannerItems === []) {
            return CheckoutResult::invalidPlanner('No valid planner items were found.');
        }

        $this->planner->resetExpiryCleanupRun();

        $checkoutItems = array_map(
            static fn(array $item): CheckoutItem => CheckoutItem::fromPlannerArray($item),
            $plannerItems
        );

        $attemptId = 0;
        $holdExpiresAt = $this->dateTimeFormatter->addSeconds(
            $this->dateTimeFormatter->currentTimestamp(),
            self::HOLD_DURATION_SECONDS
        );

        $this->pdo->beginTransaction();
        try {
            $stockResult = $this->stockReservation->reserveStockForItems($checkoutItems, $this->pdo);
            if (!$stockResult->ok) {
                $this->pdo->rollBack();

                return CheckoutResult::outOfStock($this->stockReservation->getStockConflicts($plannerItems));
            }

            $attemptId = $this->checkoutAttempts->createAttempt([
                'user_id' => $user->getId(),
                'planner_token' => $this->planner->getPlannerToken(),
                'status' => 'initiated',
                'total_price' => (float) ($planner['total_price_value'] ?? 0),
                'currency' => 'EUR',
                'hold_expires_at' => $holdExpiresAt,
                'idempotency_key' => $postedIdempotencyKey,
                'payment_provider' => null,
                'payment_reference' => null,
                'error_code' => null,
                'error_message' => null,
            ]);

            $attemptItemArrays = array_map(
                static fn(CheckoutItem $item): array => $item->toArray(),
                $checkoutItems
            );

            $this->checkoutAttempts->createAttemptItems($attemptId, $attemptItemArrays);
            $this->holdManager->createHoldsForAttempt(
                $attemptId,
                $user->getId(),
                $this->planner->getPlannerToken(),
                $attemptItemArrays,
                $holdExpiresAt,
                $this->pdo
            );

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($this->isUniqueViolation($e)) {
                $existing = $this->checkoutAttempts->findByIdempotencyKey($postedIdempotencyKey);
                if ($existing !== null) {
                    return $this->resolveExistingAttempt($existing);
                }
            }

            throw $e;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $handoff = $this->handoffService->initiatePaymentHandoff(
            $attemptId,
            $user->getId(),
            $this->planner->getPlannerToken(),
            (float) ($planner['total_price_value'] ?? 0),
            'EUR',
            $holdExpiresAt
        );

        if ($handoff->isSuccess()) {
            $this->pdo->beginTransaction();
            try {
                $this->checkoutAttempts->markHandoffCreated(
                    $attemptId,
                    'stub_provider',
                    (string) ($handoff->providerReference() ?? '')
                );
                $this->holdManager->markHoldsAsTransferred($attemptId, $this->pdo);
                $this->pdo->commit();
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                return CheckoutResult::retryRequired();
            }

            $this->planner->lock($attemptId, $holdExpiresAt);
            $this->planner->rotateIdempotencyKey();

            return CheckoutResult::handoffCreated(
                $attemptId,
                (string) ($handoff->redirectUrl() ?? '/checkout/pending/' . $attemptId)
            );
        }

        $this->pdo->beginTransaction();
        try {
            $this->stockReservation->restoreStockForAttempt($attemptId, 'handoff_failed', $this->pdo);
            $this->checkoutAttempts->markHandoffFailed(
                $attemptId,
                (string) ($handoff->errorCode() ?? 'HANDOFF_FAILED'),
                (string) ($handoff->errorMessage() ?? 'Payment provider handoff failed.')
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return CheckoutResult::retryRequired();
        }

        $this->planner->rotateIdempotencyKey();

        return CheckoutResult::handoffFailed(
            (string) ($handoff->errorMessage() ?? 'Payment provider handoff failed. Please try again.'),
            $attemptId
        );
    }

    public function confirmPendingPayment(int $checkoutAttemptId, User $user): PaymentConfirmationResult
    {
        $userId = $user->getId();

        if ($checkoutAttemptId <= 0) {
            return PaymentConfirmationResult::notFound();
        }

        if ($userId <= 0) {
            return PaymentConfirmationResult::forbidden();
        }

        $createdTickets = [];
        $invoiceId = 0;
        $attemptData = [];

        $this->pdo->beginTransaction();
        try {
            $attempt = $this->checkoutAttempts->findByIdForUpdate($checkoutAttemptId);
            if ($attempt === null) {
                $this->pdo->rollBack();
                return PaymentConfirmationResult::notFound();
            }

            if ((int) ($attempt['user_id'] ?? 0) !== $userId) {
                $this->pdo->rollBack();
                return PaymentConfirmationResult::forbidden();
            }

            $attemptData = $attempt;
            $holdExpiresAt = (string) ($attempt['hold_expires_at'] ?? '');
            if ($this->holdManager->isHoldPastGracePeriod($holdExpiresAt)) {
                $this->stockReservation->restoreStockForAttempt($checkoutAttemptId, 'expired', $this->pdo);
                $this->checkoutAttempts->markExpiredByIds([$checkoutAttemptId]);
                $this->pdo->commit();

                return PaymentConfirmationResult::expired();
            }

            $status = (string) ($attempt['status'] ?? '');
            if ($status === 'paid') {
                $this->pdo->rollBack();
                return PaymentConfirmationResult::alreadyPaid();
            }
            if ($status === 'expired') {
                $this->pdo->rollBack();
                return PaymentConfirmationResult::expired('This hold has already expired.');
            }
            if ($status === 'handoff_failed') {
                $this->pdo->rollBack();
                return PaymentConfirmationResult::failed();
            }
            if ($status !== 'handoff_created') {
                $this->pdo->rollBack();
                return PaymentConfirmationResult::invalidStatus();
            }

            $invoiceId = $this->checkoutAttempts->createInvoice($userId, (float) ($attempt['total_price'] ?? 0));
            $createdTickets = $this->checkoutAttempts->createTicketsForAttempt($checkoutAttemptId, $userId, $invoiceId);
            $this->holdManager->markHoldsAsPaid($checkoutAttemptId, $this->dateTimeFormatter->currentDateTime(), $this->pdo);
            $this->checkoutAttempts->markPaid($checkoutAttemptId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $invoiceRow = $this->checkoutAttempts->findInvoiceById($invoiceId) ?? [
            'invoice_id' => $invoiceId,
            'user_id' => $userId,
            'total_price' => (float) ($attemptData['total_price'] ?? 0),
            'issued_at' => $this->dateTimeFormatter->currentDateTime(),
            'invoice_number' => 'INV-' . $invoiceId,
            'currency' => 'EUR',
        ];

        $ticketRows = $this->checkoutAttempts->findTicketsByInvoiceId($invoiceId);
        if ($ticketRows === []) {
            $ticketRows = array_map(
                static fn(array $ticket): array => [
                    'ticket_id' => (int) ($ticket['ticket_id'] ?? 0),
                    'event_id' => (int) ($ticket['event_id'] ?? 0),
                    'event_name' => (string) ($ticket['event_name'] ?? 'Event'),
                    'event_date' => (string) ($ticket['event_date'] ?? ''),
                    'event_time' => (string) ($ticket['event_time'] ?? ''),
                    'venue' => (string) ($ticket['venue'] ?? 'Venue to be announced'),
                    'verification_code' => (string) ($ticket['verification_code'] ?? ''),
                    'family_ticket' => (bool) ($ticket['family_ticket'] ?? false),
                ],
                $createdTickets
            );
        }

        $invoiceItems = $this->buildInvoiceItemsForDelivery($ticketRows);

        $attemptDomain = CheckoutAttempt::hydrate($attemptData + [
            'checkout_attempt_id' => $checkoutAttemptId,
            'invoice_id' => $invoiceId,
            'status' => 'paid',
        ]);
        $invoiceDomain = Invoice::hydrate(
            $invoiceRow + ['invoice_id' => $invoiceId, 'user_id' => $userId],
            $invoiceItems,
            $checkoutAttemptId
        );
        $ticketDomains = array_map(
            static fn(array $ticket): Ticket => Ticket::hydrate($ticket),
            $ticketRows
        );

        $delivery = $this->ticketDelivery->deliverPurchaseEmails(
            $user,
            $attemptDomain,
            $ticketDomains,
            $invoiceDomain
        );

        return PaymentConfirmationResult::paid(
            $invoiceId,
            'Payment confirmed.',
            $delivery->emailWarning()
        );
    }

    private function resolveExistingAttempt(array $existingAttempt): CheckoutResult
    {
        $status = (string) ($existingAttempt['status'] ?? '');
        $attemptId = (int) ($existingAttempt['checkout_attempt_id'] ?? 0);

        if ($status === 'handoff_created') {
            $holdExpiresAt = (string) ($existingAttempt['hold_expires_at'] ?? '');
            $this->planner->lock($attemptId, $holdExpiresAt !== '' ? $holdExpiresAt : null);

            return CheckoutResult::alreadyPending(
                $attemptId,
                '/checkout/pending/' . $attemptId
            );
        }

        if ($status === 'handoff_failed' || $status === 'expired') {
            $this->planner->rotateIdempotencyKey();
            return CheckoutResult::retryRequired();
        }

        if ($status === 'paid') {
            return CheckoutResult::alreadyPaid($attemptId);
        }

        return CheckoutResult::alreadyProcessing();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractValidPlannerItems(array $planner): array
    {
        $items = array_values(array_filter(
            (array) ($planner['items'] ?? []),
            static fn(array $item): bool => (bool) ($item['is_valid'] ?? false)
        ));

        usort($items, static fn(array $a, array $b): int => (int) ($a['event_id'] ?? 0) <=> (int) ($b['event_id'] ?? 0));

        return $items;
    }

    private function isUniqueViolation(PDOException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062;
    }

    /**
     * @param array<int, array<string, mixed>> $tickets
     * @return array<int, array<string, mixed>>
     */
    private function buildInvoiceItemsForDelivery(array $tickets): array
    {
        $linesByKey = [];

        foreach ($tickets as $ticket) {
            $eventId = (int) ($ticket['event_id'] ?? 0);
            $ticketPrice = (float) ($ticket['ticket_price_value'] ?? ($ticket['ticket_price'] ?? 0));
            $key = $eventId . '|' . number_format($ticketPrice, 2, '.', '');

            if (!isset($linesByKey[$key])) {
                $linesByKey[$key] = [
                    'event_name' => (string) ($ticket['event_name'] ?? 'Event'),
                    'event_date' => (string) ($ticket['event_date'] ?? '-'),
                    'event_time' => (string) ($ticket['event_time'] ?? '-'),
                    'venue' => (string) ($ticket['venue'] ?? 'Venue to be announced'),
                    'quantity' => 0,
                    'unit_price_value' => $ticketPrice,
                    'line_total_value' => 0.0,
                ];
            }

            $linesByKey[$key]['quantity']++;
            $linesByKey[$key]['line_total_value'] += $ticketPrice;
        }

        return array_values($linesByKey);
    }
}
