<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

interface IPlannerService
{
	public function getPlannerToken(): string;

	public function getDetailedPlanner(): array;

	public function isLocked(): bool;

	public function getLockedCheckoutAttemptId(): ?int;

	public function lock(int $attemptId, ?string $holdExpiresAt = null): void;

	public function unlock(): void;

	public function unlockIfAttemptId(int $attemptId): void;

	public function unlockIfExpired(array $expiredAttemptIds): bool;

	public function addItem(int $eventId, int $quantity, ?string $familyTicket): void;

	public function addItems(array $eventIds, int $quantity): int;

	public function updateItemQuantity(int $eventId, int $quantity): void;

	public function removeItem(int $eventId): void;

	public function clear(): void;

	public function getIdempotencyKey(): string;

	public function rotateIdempotencyKey(): string;

	public function setFlash(string $type, string $message): void;

	public function consumeFlash(): ?array;
}
