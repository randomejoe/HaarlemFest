<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Evaluates whether ticket holds have expired based on grace period logic.
 *
 * Centralizes the grace period calculation that was previously duplicated
 * in CheckoutHoldManager::isHoldPastGracePeriod() and checked manually
 * in CheckoutService::confirmPendingPayment().
 *
 * Single source of truth for:
 * - Grace period duration
 * - Expiry calculation
 * - Timestamp parsing and comparison
 */
final class HoldExpiryEvaluator
{
	private const EXPIRY_GRACE_PERIOD_SECONDS = 30;

	public function __construct(
		private DateTimeFormatter $dateTimeFormatter
	) {}

	/**
	 * Check if a hold has passed its expiry + grace period.
	 *
	 * A hold is considered "past grace period" when:
	 * hold_expiry_timestamp + GRACE_PERIOD_SECONDS <= current_time
	 *
	 * This is used to prevent payment confirmation on expired holds
	 * even if they're still within a brief grace window for network delays.
	 *
	 * @param string $holdExpiresAt  ISO 8601 datetime or strtotime-parseable string
	 * @return bool                   True if hold is considered expired
	 */
	public function isPastGracePeriod(string $holdExpiresAt): bool
	{
		if ($holdExpiresAt === '') {
			return false;
		}

		$expiryTimestamp = strtotime($holdExpiresAt);
		if ($expiryTimestamp === false) {
			return false;
		}

		return ($expiryTimestamp + self::EXPIRY_GRACE_PERIOD_SECONDS) <= $this->dateTimeFormatter->currentTimestamp();
	}

	/**
	 * Get the grace period duration in seconds.
	 */
	public function getGracePeriodSeconds(): int
	{
		return self::EXPIRY_GRACE_PERIOD_SECONDS;
	}
}
