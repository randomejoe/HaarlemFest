<?php

namespace App\Services;

use App\Config;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;

class CaptchaService
{
    private Altcha $altcha;

    public function __construct()
    {
        $hmacKey = Config::env('ALTCHA_HMAC_KEY', 'dev-altcha-hmac-key');
        $this->altcha = new Altcha($hmacKey);
    }

    public function createChallenge(): \AltchaOrg\Altcha\Challenge
    {
        $options = new ChallengeOptions();
        return $this->altcha->createChallenge($options);
    }

    public function verify(?string $payload): bool
    {
        if ($payload === null || $payload === '') {
            return false;
        }

        $data = $payload;
        $trimmed = ltrim($payload);
        if ($trimmed !== '' && $trimmed[0] === '{') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        return $this->altcha->verifySolution($data, true);
    }
}
