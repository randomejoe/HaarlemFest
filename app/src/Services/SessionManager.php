<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Centralizes all session state management for planner and checkout flows.
 *
 * Replaces scattered $_SESSION access across PlannerService, CheckoutService,
 * and related services. Provides a single API for session concerns:
 * - Planner state (items, lock info, idempotency key)
 * - Flash messages
 * - Expiry cleanup tracking
 *
 * Benefits:
 * - Easier to test (can mock session behavior)
 * - Clearer session contract
 * - Centralized initialization logic
 * - Easier to add session concerns (e.g., CSRF tokens)
 */
final class SessionManager
{
	private const SESSION_KEY = 'planner';
	private const TOKEN_KEY = 'planner_token';
	private const FLASH_KEY = 'planner_flash';
	private const EXPIRY_CLEANUP_KEY = 'planner_expiry_cleanup';

	public function __construct()
	{
		$this->ensureInitialized();
	}

	/**
	 * Get the planner token (session identifier for tracking purposes).
	 */
	public function getPlannerToken(): string
	{
		$this->ensureInitialized();
		return (string) $_SESSION[self::TOKEN_KEY];
	}

	/**
	 * Get the entire planner state object.
	 *
	 * @return array<string, mixed>
	 */
	public function getPlannerState(): array
	{
		$this->ensureInitialized();
		/** @var array<string, mixed> $planner */
		$planner = $_SESSION[self::SESSION_KEY];
		return $planner;
	}

	/**
	 * Persist planner state to the session.
	 *
	 * @param array<string, mixed> $planner
	 */
	public function setPlannerState(array $planner): void
	{
		$_SESSION[self::SESSION_KEY] = [
			'items' => $this->normalizeItems((array) ($planner['items'] ?? [])),
			'locked_checkout_attempt_id' => $planner['locked_checkout_attempt_id'] ?? null,
			'locked_checkout_expires_at' => $planner['locked_checkout_expires_at'] ?? null,
			'idempotency_key' => (string) ($planner['idempotency_key'] ?? $this->generateToken()),
			'updated_at' => (int) ($planner['updated_at'] ?? time()),
		];
	}

	/**
	 * Set a flash message to be consumed on the next page load.
	 */
	public function setFlash(string $type, string $message): void
	{
		$_SESSION[self::FLASH_KEY] = [
			'type' => $type,
			'message' => $message,
		];
	}

	/**
	 * Consume and clear the flash message.
	 *
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
			'type' => (string) ($flash['type'] ?? 'info'),
			'message' => (string) ($flash['message'] ?? ''),
		];
	}

	/**
	 * Check if cleanup has run recently (within cooldown).
	 */
	public function shouldRunExpiryCleanup(int $cooldownSeconds): bool
	{
		if ($cooldownSeconds <= 0) {
			return true;
		}

		$lastRunAt = $this->lastCleanupRunAt();
		if ($lastRunAt === null) {
			return true;
		}

		return ($lastRunAt + $cooldownSeconds) <= time();
	}

	/**
	 * Record that expiry cleanup just ran.
	 */
	public function markExpiryCleanupRun(?int $timestamp = null): void
	{
		$_SESSION[self::EXPIRY_CLEANUP_KEY] = [
			'last_run_at' => $timestamp ?? time(),
		];
	}

	/**
	 * Clear the expiry cleanup state (used when planner is reset).
	 */
	public function resetExpiryCleanupRun(): void
	{
		unset($_SESSION[self::EXPIRY_CLEANUP_KEY]);
	}

	/**
	 * Get the timestamp of the last expiry cleanup run.
	 */
	private function lastCleanupRunAt(): ?int
	{
		$state = $_SESSION[self::EXPIRY_CLEANUP_KEY] ?? null;
		if (!is_array($state)) {
			return null;
		}

		$lastRunAt = (int) ($state['last_run_at'] ?? 0);
		return $lastRunAt > 0 ? $lastRunAt : null;
	}

	/**
	 * Generate a secure random token for planner/checkout tracking.
	 */
	public function generateToken(): string
	{
		return bin2hex(random_bytes(32));
	}

	/**
	 * Normalize item quantities: remove zeros, free items, and invalid entries.
	 *
	 * @param  array<int|string, int> $items  Map of event_id => quantity
	 * @return array<int, int>                 Normalized map with int keys
	 */
	private function normalizeItems(array $items): array
	{
		$normalized = [];
		foreach ($items as $eventId => $quantity) {
			$eventId = (int) $eventId;
			$quantity = (int) $quantity;

			if ($eventId <= 0 || $quantity <= 0) {
				continue;
			}

			$normalized[$eventId] = $quantity;
		}

		return $normalized;
	}

	/**
	 * Ensure session state is initialized with defaults.
	 */
	private function ensureInitialized(): void
	{
		if (!isset($_SESSION[self::TOKEN_KEY]) || !is_string($_SESSION[self::TOKEN_KEY]) || $_SESSION[self::TOKEN_KEY] === '') {
			$_SESSION[self::TOKEN_KEY] = $this->generateToken();
		}

		if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
			$_SESSION[self::SESSION_KEY] = $this->getDefaultPlannerState();
			return;
		}

		$planner = $_SESSION[self::SESSION_KEY];
		$planner['items'] = $this->normalizeItems((array) ($planner['items'] ?? []));

		if (!array_key_exists('locked_checkout_attempt_id', $planner)) {
			$planner['locked_checkout_attempt_id'] = null;
		}

		if (!array_key_exists('locked_checkout_expires_at', $planner)) {
			$planner['locked_checkout_expires_at'] = null;
		}

		if (!isset($planner['idempotency_key']) || !is_string($planner['idempotency_key']) || $planner['idempotency_key'] === '') {
			$planner['idempotency_key'] = $this->generateToken();
		}

		$planner['updated_at'] = (int) ($planner['updated_at'] ?? time());

		$_SESSION[self::SESSION_KEY] = $planner;
	}

	/**
	 * Get default planner state structure.
	 *
	 * @return array<string, mixed>
	 */
	private function getDefaultPlannerState(): array
	{
		return [
			'items' => [],
			'locked_checkout_attempt_id' => null,
			'locked_checkout_expires_at' => null,
			'idempotency_key' => $this->generateToken(),
			'updated_at' => time(),
		];
	}
}
