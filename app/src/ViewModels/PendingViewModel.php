<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\CheckoutAttempt;
use App\Models\User;

final class PendingViewModel
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        private CheckoutAttempt $attempt,
        private array $items = [],
        private ?User $user = null,
        private ?array $flash = null,
        private ?string $csrfToken = null
    ) {
    }

    public function attempt(): CheckoutAttempt
    {
        return $this->attempt;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return [
            'attempt' => $this->attempt->toArray(),
            'items' => $this->items,
            'user' => $this->user,
            'flash' => $this->flash,
            'csrf_token' => $this->csrfToken,
        ];
    }
}
