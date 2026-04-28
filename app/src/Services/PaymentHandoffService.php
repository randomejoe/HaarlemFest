<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentHandoffResponse;
use App\Services\Interfaces\IPaymentHandoffService;

final class PaymentHandoffService implements IPaymentHandoffService
{
    public function __construct(
        private PaymentGatewayStubService $paymentGateway,
    ) {
    }

    public function initiatePaymentHandoff(
        int $attemptId,
        int $userId,
        string $plannerToken,
        float $amount,
        string $currency,
        string $holdExpiresAt
    ): PaymentHandoffResponse {
        $handoff = $this->paymentGateway->createTransaction([
            'checkout_attempt_id' => $attemptId,
            'user_id' => $userId,
            'planner_token' => $plannerToken,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return new PaymentHandoffResponse(
            (bool) ($handoff['success'] ?? false),
            isset($handoff['provider_reference']) ? (string) $handoff['provider_reference'] : null,
            isset($handoff['redirect_url']) ? (string) $handoff['redirect_url'] : null,
            isset($handoff['error_code']) ? (string) $handoff['error_code'] : null,
            isset($handoff['error_message']) ? (string) $handoff['error_message'] : null,
        );
    }
}
