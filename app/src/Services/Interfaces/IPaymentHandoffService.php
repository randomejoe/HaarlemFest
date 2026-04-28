<?php

namespace App\Services\Interfaces;

use App\Models\PaymentHandoffResponse;

interface IPaymentHandoffService
{
    public function initiatePaymentHandoff(
        int $attemptId,
        int $userId,
        string $plannerToken,
        float $amount,
        string $currency,
        string $holdExpiresAt
    ): PaymentHandoffResponse;
}
