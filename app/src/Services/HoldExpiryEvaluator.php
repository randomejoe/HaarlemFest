<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Evaluates whether ticket holds have expired.
 *
 * Single source of truth for expiry calculation and timestamp parsing.
 */
final class HoldExpiryEvaluator
{
	public function __construct(
		private DateTimeFormatter $dateTimeFormatter
	) {}

	/**
	 * Check if a hold has reached or passed its expiry timestamp.
	 *
	 * @param string $holdExpiresAt  ISO 8601 datetime or strtotime-parseable string
	 * @return bool                   True if hold is considered expired
	 */
	public function isExpired(string $holdExpiresAt): bool
	{
		if ($holdExpiresAt === '') {
			return false;
		}

		$expiryTimestamp = strtotime($holdExpiresAt);
		if ($expiryTimestamp === false) {
			return false;
		}

		return $expiryTimestamp <= $this->dateTimeFormatter->currentTimestamp();
	}
}
