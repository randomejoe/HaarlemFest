<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\PlannerSummary;
use App\Models\User;

final class CheckoutViewModel
{
    /**
     * @param array<string, mixed> $missingDetails
     */
    public function __construct(
        private PlannerSummary $planner,
        private User $user,
        private bool $requiresDetails,
        private array $missingDetails = [],
        private ?array $flash = null,
        private ?string $csrfToken = null
    ) {
    }

    public function planner(): PlannerSummary
    {
        return $this->planner;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function requiresDetails(): bool
    {
        return $this->requiresDetails;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'planner' => $this->planner->toArray(),
            'user' => $this->user,
            'requires_details' => $this->requiresDetails,
            'missing_details' => $this->missingDetails,
            'flash' => $this->flash,
            'csrf_token' => $this->csrfToken,
        ];
    }
}
