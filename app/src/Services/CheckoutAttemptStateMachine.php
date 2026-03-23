<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Manages the state transitions and validation for checkout attempts.
 *
 * Centralizes the duplicated status checking logic from:
 * - CheckoutService::confirmPendingPayment() (4+ status checks)
 * - CheckoutService::resolveExistingAttempt() (4+ status checks)
 * - And various other status comparisons
 *
 * Provides:
 * - Clear state machine documentation
 * - Validation of state transitions
 * - Single source of truth for valid states
 *
 * State transitions:
 * initiated → handoff_created → paid
 *          ↘ handoff_failed (payment provider error)
 *          ↘ expired (grace period elapsed)
 */
final class CheckoutAttemptStateMachine
{
	// Valid states
	public const STATE_INITIATED = 'initiated';           // Initial state, stock reserved
	public const STATE_HANDOFF_CREATED = 'handoff_created'; // Payment provider URL delivered
	public const STATE_PAID = 'paid';                   // Payment confirmed, invoice created, tickets delivered
	public const STATE_HANDOFF_FAILED = 'handoff_failed';   // Payment provider handoff failed
	public const STATE_EXPIRED = 'expired';               // Hold grace period elapsed

	public const VALID_STATES = [
		self::STATE_INITIATED,
		self::STATE_HANDOFF_CREATED,
		self::STATE_PAID,
		self::STATE_HANDOFF_FAILED,
		self::STATE_EXPIRED,
	];

	// Terminal states (no further transitions possible)
	public const TERMINAL_STATES = [
		self::STATE_PAID,
		self::STATE_EXPIRED,
	];

	/**
	 * Check if a state is valid.
	 */
	public static function isValidState(string $state): bool
	{
		return in_array($state, self::VALID_STATES, true);
	}

	/**
	 * Check if a state is terminal (no further transitions).
	 */
	public static function isTerminalState(string $state): bool
	{
		return in_array($state, self::TERMINAL_STATES, true);
	}

	/**
	 * Check if the state represents a failed/terminal condition.
	 */
	public static function isFailed(string $state): bool
	{
		return $state === self::STATE_EXPIRED || $state === self::STATE_HANDOFF_FAILED;
	}

	/**
	 * Check if the state represents a successful condition.
	 */
	public static function isSuccess(string $state): bool
	{
		return $state === self::STATE_PAID;
	}

	/**
	 * Check if the state is waiting for payment confirmation.
	 */
	public static function isPendingPaymentConfirmation(string $state): bool
	{
		return $state === self::STATE_HANDOFF_CREATED;
	}

	/**
	 * Get a human-readable description of the state.
	 */
	public static function getDescription(string $state): string
	{
		return match ($state) {
			self::STATE_INITIATED => 'Stock reserved, waiting for payment handoff',
			self::STATE_HANDOFF_CREATED => 'Payment handoff created, awaiting confirmation',
			self::STATE_PAID => 'Payment confirmed, tickets delivered',
			self::STATE_HANDOFF_FAILED => 'Payment handoff failed',
			self::STATE_EXPIRED => 'Hold expired before payment',
			default => 'Unknown state',
		};
	}
}
