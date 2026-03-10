<?php

namespace App\Services;

class PaymentGatewayStubService
{
    public function createTransaction(array $payload, bool $simulateFailure = false): array
    {
        if ($simulateFailure) {
            return [
                'success' => false,
                'provider_reference' => null,
                'redirect_url' => null,
                'error_code' => 'HANDOFF_FAILED',
                'error_message' => 'Payment provider handoff failed. Please try again.',
            ];
        }

        $reference = 'stub_' . bin2hex(random_bytes(8));

        return [
            'success' => true,
            'provider_reference' => $reference,
            'redirect_url' => '/checkout/pending/' . (int) ($payload['checkout_attempt_id'] ?? 0),
            'error_code' => null,
            'error_message' => null,
        ];
    }
}
