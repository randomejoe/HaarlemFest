<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;

interface ICheckoutService
{
	public function buildCheckoutView(User $user): array;

	public function loadCheckoutUser(int $userId): ?User;

	public function missingCheckoutDetails(User $user): array;

	public function saveCheckoutDetails(int $userId, array $details): void;

	public function setFlash(string $type, string $message): void;

	public function confirmCheckout(User $user): array;
}
