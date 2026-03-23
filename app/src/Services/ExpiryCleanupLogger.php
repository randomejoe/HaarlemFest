<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Centralizes expiry cleanup event logging.
 *
 * Replaces duplicated logExpiryCleanup() methods in CheckoutService
 * and CheckoutHoldManager. Provides a consistent logging interface
 * for cleanup operations.
 *
 * Benefits:
 * - Single logging implementation (currently duplicated in 2 services)
 * - Easier to change logging format/destination
 * - Clear separation of concerns (logging vs. cleanup logic)
 */
final class ExpiryCleanupLogger
{
	/**
	 * Log an expiry cleanup event.
	 *
	 * @param string $event         The event type ('executed', 'skipped', etc.)
	 * @param array<string, mixed> $context  Additional context key-value pairs
	 */
	public function log(string $event, array $context): void
	{
		$parts = [];
		foreach ($context as $key => $value) {
			if (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			}

			$parts[] = $key . '=' . $value;
		}

		error_log('expiry_cleanup ' . $event . ' ' . implode(' ', $parts));
	}

	/**
	 * Log an executed cleanup event.
	 */
	public function logExecuted(int $releasedCount, int $expiredAttemptCount, bool $forced = false): void
	{
		$this->log('executed', [
			'force' => $forced,
			'released_count' => $releasedCount,
			'expired_attempt_count' => $expiredAttemptCount,
		]);
	}

	/**
	 * Log a skipped cleanup event.
	 */
	public function logSkipped(bool $forced = false, string $reason = 'cooldown', int $cooldownSeconds = 60): void
	{
		$this->log('skipped', [
			'force' => $forced,
			'skip_reason' => $reason,
			'cooldown_seconds' => $cooldownSeconds,
		]);
	}
}
