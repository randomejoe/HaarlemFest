<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\PlannerSummary;

interface IPlannerService
{
	public function getItems(): array;

	public function getPlannerSummary(): PlannerSummary;

	public function pruneUnavailableItems(): void;

	public function getDetailedPlanner(): array;

	public function addItem(int $eventId, int $quantity, ?string $familyTicket): void;

	public function addItems(array $items, int $quantity): int;

	public function updateItemQuantity(int $eventId, int $quantity): void;

	public function removeItem(int $eventId): void;

	public function clear(): void;

	public function setFlash(\App\Models\FlashType $type, string $message): void;

	public function consumeFlash(): ?array;
}
