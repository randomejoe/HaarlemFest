<?php

namespace App\Services;

class PaymentGatewayStubService
{
    public function createTransaction(array $payload): array
    {
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
