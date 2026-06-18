<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlashType;

final class SessionManager
{
	private const SESSION_KEY = 'planner';
	private const FLASH_KEY = 'planner_flash';

	public function __construct()
	{
		$this->ensureInitialized();
	}

	/**
	 * @return array<int, array{quantity:int, familyTicket:bool}>
	 */
	public function getPlannerItems(): array
	{
		$this->ensureInitialized();
		$planner = (array) ($_SESSION[self::SESSION_KEY] ?? []);
		return $this->normalizeItems((array) ($planner['items'] ?? []));
	}

	public function setPlannerItems(array $items): void
	{
		$this->ensureInitialized();
		$_SESSION[self::SESSION_KEY]['items'] = $this->normalizeItems($items);
	}

	public function removePlannerItem(string $key): void
	{
		$items = $this->getPlannerItems();
		unset($items[(int) $key]);
		$this->setPlannerItems($items);
	}

	public function clearPlanner(): void
	{
		$this->setPlannerItems([]);
	}

	public function setFlash(FlashType $type, string $message): void
	{
		$_SESSION[self::FLASH_KEY] = [
			'type' => $type->value,
			'message' => $message,
		];
	}

	/**
	 * @return array{type: string, message: string}|null
	 */
	public function consumeFlash(): ?array
	{
		if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
			return null;
		}

		$flash = $_SESSION[self::FLASH_KEY];
		unset($_SESSION[self::FLASH_KEY]);

		return [
			'type' => $flash['type'],
			'message' => (string) ($flash['message'] ?? ''),
		];
	}

	public function generateToken(): string
	{
		return bin2hex(random_bytes(32));
	}

	/**
	 * @return array<int, array{quantity:int, familyTicket:bool}>
	 */
	private function normalizeItems(array $items): array
	{
		$normalized = [];
		foreach ($items as $eventIdRaw => $item) {
			$eventId = (int) $eventIdRaw;

			if (is_array($item)) {
				$quantity = max(0, (int) ($item['quantity'] ?? 0));
				$familyTicket = (bool) ($item['familyTicket'] ?? false);
			} else {
				$quantity = max(0, (int) $item);
				$familyTicket = false;
			}

			if ($eventId <= 0 || $quantity <= 0) {
				continue;
			}

			$normalized[$eventId] = [
				'quantity' => $quantity,
				'familyTicket' => $familyTicket,
			];
		}

		return $normalized;
	}

	private function ensureInitialized(): void
	{
		if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
			$_SESSION[self::SESSION_KEY] = ['items' => []];
			return;
		}

		$planner = $_SESSION[self::SESSION_KEY];
		$planner['items'] = $this->normalizeItems((array) ($planner['items'] ?? []));
		$_SESSION[self::SESSION_KEY] = $planner;
	}
}
