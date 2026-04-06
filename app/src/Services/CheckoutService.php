<?php

namespace App\Services;

use App\Models\CheckoutItem;
use App\Models\User;
use App\Repositories\CheckoutRepository;
use App\Services\Results\CheckoutResult;
use App\Services\Results\HoldExpiryResult;
use App\Services\Results\PaymentConfirmationResult;
use PDO;
use Throwable;

class CheckoutService
{
    public function __construct(
        private PDO $pdo,
        private PlannerService $planner,
        private CheckoutRepository $checkoutAttempts,
        private CheckoutHoldManager $holdManager,
        private HoldExpiryEvaluator $expiryEvaluator,
        private DateTimeFormatter $dateTimeFormatter,
        private CheckoutValidationService $validation,
        private StockReservationService $stockReservation,
        private PaymentHandoffService $handoffService,
        private TicketDeliveryOrchestrator $deliveryOrchestrator
    ) {}

    public function releaseExpiredHolds(): HoldExpiryResult
    {
        return $this->holdManager->releaseExpiredHolds();
    }

    public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult
    {
        return $this->holdManager->releaseExpiredHoldsIfNeeded($this->planner, $force);
    }

    public function confirmCheckout(User $user, string $postedIdempotencyKey): CheckoutResult
    {
        if ($this->planner->isLocked()) {
            return new CheckoutResult(
                'locked',
                'Your planner is locked while payment is pending.',
                $this->planner->getLockedCheckoutAttemptId()
            );
        }

        if (!$this->validation->isValidIdempotencyKey($postedIdempotencyKey)) {
            return new CheckoutResult(
                'invalid_request',
                'This checkout request is no longer valid. Please try again.'
            );
        }

        $existingAttempt = $this->checkoutAttempts->findByIdempotencyKey($postedIdempotencyKey);
        if ($existingAttempt !== null) {
            return $this->resolveExistingAttempt($existingAttempt);
        }

        $checkoutPayload = $this->validation->prepareCheckoutPayload();
        if ($checkoutPayload['error'] instanceof CheckoutResult) {
            return $checkoutPayload['error'];
        }

        $planner = $checkoutPayload['planner'];
        $items = $checkoutPayload['items'];

        $this->planner->resetExpiryCleanupRun();
        $checkoutItems = array_map(
            static fn(array $item): CheckoutItem => CheckoutItem::fromPlannerArray($item),
            $items
        );

        $result = $this->transaction(function () use ($user, $planner, $checkoutItems, $postedIdempotencyKey): CheckoutResult {
            $pending = $this->startPendingAttempt(
                $user,
                $planner,
                $checkoutItems,
                $postedIdempotencyKey
            );

            $attemptId = (int) ($pending['attempt_id'] ?? 0);
            $holdExpiresAt = (string) ($pending['hold_expires_at'] ?? '');

            if ($attemptId === 0) {
                return new CheckoutResult(
                    'out_of_stock',
                    'Some items are no longer available in the requested quantities.',
                    conflicts: $this->stockReservation->getStockConflicts($items)
                );
            }

            $result = $this->handoffService->initiatePaymentHandoff(
                $attemptId,
                $user->id(),
                $this->planner->getPlannerToken(),
                (float) $planner['total_price_value'],
                'EUR',
                $holdExpiresAt
            );

            if ($result->isSuccess()) {
                $this->holdManager->markHoldsAsTransferred($attemptId);
            }

            return $result;
        });

        return $result;
    }

    public function confirmPendingPayment(int $checkoutAttemptId, User $user): PaymentConfirmationResult
    {
        $userId = $user->id();

        if ($checkoutAttemptId <= 0) {
            return new PaymentConfirmationResult(
                'not_found',
                'Checkout attempt not found.'
            );
        }

        if ($userId <= 0) {
            return new PaymentConfirmationResult(
                'forbidden',
                'You are not allowed to confirm this payment.'
            );
        }

        $attemptData = [];
        $createdTickets = [];

        $paymentResult = $this->transaction(function () use ($checkoutAttemptId, $userId, &$attemptData, &$createdTickets): PaymentConfirmationResult {
            $attempt = $this->checkoutAttempts->findByIdForUpdate($checkoutAttemptId);
            if ($attempt === null) {
                return new PaymentConfirmationResult(
                    'not_found',
                    'Checkout attempt not found.'
                );
            }

            if ((int) ($attempt['user_id'] ?? 0) !== $userId) {
                return new PaymentConfirmationResult(
                    'forbidden',
                    'You are not allowed to confirm this payment.'
                );
            }

            $attempt = (array) $attempt;

            $holdExpiresAt = (string) ($attempt['hold_expires_at'] ?? '');
            if ($this->expiryEvaluator->isExpired($holdExpiresAt)) {
                $this->stockReservation->releaseAndRestoreStock($checkoutAttemptId, 'expired');
                $this->checkoutAttempts->markExpiredByIds([$checkoutAttemptId]);

                return new PaymentConfirmationResult(
                    'expired',
                    'This hold expired before payment confirmation.'
                );
            }

            $status = (string) ($attempt['status'] ?? '');
            if ($status === 'paid') {
                return new PaymentConfirmationResult(
                    'already_paid',
                    'Payment was already confirmed for this attempt.'
                );
            }
            if ($status === 'expired') {
                return new PaymentConfirmationResult(
                    'expired',
                    'This hold has already expired.'
                );
            }
            if ($status === 'handoff_failed') {
                return new PaymentConfirmationResult(
                    'failed',
                    'This checkout handoff failed and cannot be confirmed.'
                );
            }
            if ($status !== 'handoff_created') {
                return new PaymentConfirmationResult(
                    'invalid_status',
                    'This checkout attempt is not waiting for payment confirmation.'
                );
            }

            $invoiceId = $this->checkoutAttempts->createInvoice($userId, (float) ($attempt['total_price'] ?? 0));
            $createdTicketsLocal = $this->checkoutAttempts->createTicketsForAttempt($checkoutAttemptId, $userId, $invoiceId);
            $this->holdManager->markHoldsAsPaid($checkoutAttemptId, $this->dateTimeFormatter->currentDateTime());
            $this->checkoutAttempts->markPaid($checkoutAttemptId);

            $attempt['invoice_id'] = $invoiceId;
            $attemptData = $attempt;
            $createdTickets = $createdTicketsLocal;

            return new PaymentConfirmationResult(
                'paid',
                'Payment confirmed.',
                $invoiceId
            );
        });

        if (!$paymentResult->isPaid()) {
            return $paymentResult;
        }

        $emailResult = $this->deliveryOrchestrator->deliverPurchaseEmails(
            $user,
            $attemptData,
            $createdTickets
        );

        return new PaymentConfirmationResult(
            'paid',
            $emailResult['message'],
            $paymentResult->getInvoiceId(),
            $emailResult['email_warning']
        );
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

    /**
     * @return array{attempt_id:int, hold_expires_at:string}
     */
    private function startPendingAttempt(
        User $user,
        array $planner,
        array $checkoutItems,
        string $postedIdempotencyKey
    ): array {
        $failedEventIds = $this->stockReservation->reserveStockForItems($checkoutItems);
        if ($failedEventIds !== []) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['attempt_id' => 0, 'hold_expires_at' => ''];
        }

        $expiresAt = $this->dateTimeFormatter->addSeconds(
            $this->dateTimeFormatter->currentTimestamp(),
            600
        );

        $attemptId = $this->checkoutAttempts->createAttempt([
            'user_id'          => $user->id(),
            'planner_token'    => $this->planner->getPlannerToken(),
            'status'           => 'initiated',
            'total_price'      => (float) ($planner['total_price_value'] ?? 0),
            'currency'         => 'EUR',
            'hold_expires_at'  => $expiresAt,
            'idempotency_key'  => $postedIdempotencyKey,
            'payment_provider' => null,
            'payment_reference' => null,
            'error_code'       => null,
            'error_message'    => null,
        ]);

        $attemptItemArrays = array_map(static fn(CheckoutItem $ci): array => $ci->toArray(), $checkoutItems);
        $this->checkoutAttempts->createAttemptItems($attemptId, $attemptItemArrays);
        $this->holdManager->createHoldsForAttempt(
            $attemptId,
            $user->id(),
            $this->planner->getPlannerToken(),
            $attemptItemArrays,
            $expiresAt
        );

        return ['attempt_id' => $attemptId, 'hold_expires_at' => $expiresAt];
    }

    private function resolveExistingAttempt(array $existingAttempt): CheckoutResult
    {
        $status = (string) ($existingAttempt['status'] ?? '');

        if ($status === 'handoff_created') {
            $attemptId = (int) ($existingAttempt['checkout_attempt_id'] ?? 0);
            $holdExpiresAt = (string) ($existingAttempt['hold_expires_at'] ?? '');
            $this->planner->lock($attemptId, $holdExpiresAt !== '' ? $holdExpiresAt : null);

            return new CheckoutResult(
                'already_pending',
                'Payment is already pending for this checkout.',
                $attemptId,
                '/checkout/pending/' . $attemptId
            );
        }

        if ($status === 'handoff_failed' || $status === 'expired') {
            $this->planner->rotateIdempotencyKey();

            return new CheckoutResult(
                'retry_required',
                'Please retry checkout. The previous attempt could not be completed.'
            );
        }

        if ($status === 'paid') {
            return new CheckoutResult(
                'already_paid',
                'This checkout attempt was already confirmed as paid.',
                (int) ($existingAttempt['checkout_attempt_id'] ?? 0)
            );
        }

        return new CheckoutResult(
            'already_processing',
            'Checkout is already being processed. Please wait.'
        );
    }
}
