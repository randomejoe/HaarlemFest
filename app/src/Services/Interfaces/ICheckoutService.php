<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;
use App\Models\CheckoutResult;
use App\Models\HoldExpiryResult;
use App\Models\PaymentConfirmationResult;

interface ICheckoutService
{
	public function isPlannerLocked(): bool;

	public function getLockedAttemptId(): ?int;

	public function unlockIfAttemptId(int $attemptId): void;

	public function clearPlannerIfUnlocked(): void;

	public function consumeFlash(): ?array;

	public function setFlash(string $type, string $message): void;

	public function getIdempotencyKey(): string;

	public function buildCheckoutView(User $user): array;

	public function buildPendingView(int $attemptId, User $user): array;

	public function loadCheckoutUser(int $userId): ?User;

	public function missingCheckoutDetails(User $user): array;

	public function saveCheckoutDetails(int $userId, array $details): void;

	public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult;

	public function confirmCheckout(User $user, string $postedIdempotencyKey): CheckoutResult;

	public function confirmPendingPayment(int $checkoutAttemptId, User $user): PaymentConfirmationResult;
}
