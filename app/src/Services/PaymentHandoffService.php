<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CheckoutRepository;
use App\Services\Results\CheckoutResult;
use PDO;

final class PaymentHandoffService
{
    public function __construct(
        private PaymentGatewayStubService $paymentGateway,
        private CheckoutRepository $checkoutAttempts,
        private StockReservationService $stockReservation,
        private PlannerService $planner,
        private PDO $pdo,
    ) {}

    public function initiatePaymentHandoff(
        int $attemptId,
        int $userId,
        string $plannerToken,
        float $amount,
        string $currency,
        string $holdExpiresAt
    ): CheckoutResult {
        $handoff = $this->paymentGateway->createTransaction([
            'checkout_attempt_id' => $attemptId,
            'user_id' => $userId,
            'planner_token' => $plannerToken,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        if (!(bool) ($handoff['success'] ?? false)) {
            return $this->handleFailedHandoff($attemptId, $handoff);
        }

        $this->markAttemptAsHandedOff($attemptId, $handoff);
        $this->planner->lock($attemptId, $holdExpiresAt);
        $this->planner->rotateIdempotencyKey();

        return new CheckoutResult(
            'handoff_created',
            'Payment handoff created. Continue to pending payment status.',
            $attemptId,
            (string) ($handoff['redirect_url'] ?? '/checkout/pending/' . $attemptId),
            (string) ($handoff['provider_reference'] ?? '')
        );
    }

    private function handleFailedHandoff(int $attemptId, array $handoff): CheckoutResult
    {
        $this->stockReservation->releaseAndRestoreStock($attemptId, 'handoff_failed');
        $this->checkoutAttempts->markHandoffFailed(
            $attemptId,
            (string) ($handoff['error_code'] ?? 'HANDOFF_FAILED'),
            (string) ($handoff['error_message'] ?? 'Payment provider handoff failed.')
        );

        // Only release the planner lock (and rotate the idempotency key) if this
        // failed handoff actually owns the currently stored lock.
        if ($this->planner->unlockIfAttemptId($attemptId)) {
            $this->planner->rotateIdempotencyKey();
        }

        return new CheckoutResult(
            'handoff_failed',
            (string) ($handoff['error_message'] ?? 'Payment provider handoff failed. Please try again.'),
            $attemptId
        );
    }

    private function markAttemptAsHandedOff(int $attemptId, array $handoff): void
    {
        $this->checkoutAttempts->markHandoffCreated(
            $attemptId,
            'stub_provider',
            (string) ($handoff['provider_reference'] ?? '')
        );
    }
}
