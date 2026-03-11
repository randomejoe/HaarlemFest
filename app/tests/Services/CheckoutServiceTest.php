<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Models\User;
use App\Repositories\CheckoutRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketHoldRepository;
use App\Services\CheckoutService;
use App\Services\PaymentGatewayStubService;
use App\Services\PlannerService;
use App\Services\TicketDeliveryService;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutServiceTest extends TestCase
{
    private PDO&MockObject $pdo;
    private PlannerService&MockObject $planner;
    private EventRepository&MockObject $events;
    private CheckoutRepository&MockObject $checkoutAttempts;
    private TicketHoldRepository&MockObject $ticketHolds;
    private PaymentGatewayStubService&MockObject $paymentGateway;
    private TicketDeliveryService&MockObject $ticketDelivery;

    private CheckoutService $sut;
    private User $user;

    protected function setUp(): void
    {
        $this->pdo              = $this->createMock(PDO::class);
        $this->planner          = $this->createMock(PlannerService::class);
        $this->events           = $this->createMock(EventRepository::class);
        $this->checkoutAttempts = $this->createMock(CheckoutRepository::class);
        $this->ticketHolds      = $this->createMock(TicketHoldRepository::class);
        $this->paymentGateway   = $this->createMock(PaymentGatewayStubService::class);
        $this->ticketDelivery   = $this->createMock(TicketDeliveryService::class);

        $this->sut = new CheckoutService(
            $this->pdo,
            $this->planner,
            $this->events,
            $this->checkoutAttempts,
            $this->ticketHolds,
            $this->paymentGateway,
            $this->ticketDelivery
        );

        $this->user = User::fromArray([
            'user_id'  => 1,
            'username' => 'testuser',
            'email'    => 'test@example.com',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Set planner to not-locked and make the posted idempotency key valid. */
    private function withUnlockedPlanner(string $key = 'test-key'): void
    {
        $this->planner->method('isLocked')->willReturn(false);
        $this->planner->method('getIdempotencyKey')->willReturn($key);
    }

    /** Return a minimal getDetailedPlanner() payload. */
    private function plannerPayload(bool $isEmpty, bool $hasInvalidItems, array $items = []): array
    {
        return [
            'is_empty'         => $isEmpty,
            'has_invalid_items' => $hasInvalidItems,
            'items'            => $items,
            'total_price_value' => 0.0,
        ];
    }

    /**
     * Return a single valid planner-item array (PlannerItem::toArray() shape).
     * The 'is_valid' flag makes extractValidPlannerItems() keep it;
     * the price fields feed CheckoutItem::fromPlannerArray().
     */
    private function validPlannerItem(int $eventId = 1, string $name = 'Jazz Night', int $qty = 2, float $unitPrice = 15.00): array
    {
        return [
            'event_id'         => $eventId,
            'name'             => $name,
            'quantity'         => $qty,
            'is_valid'         => true,
            'unit_price_value' => $unitPrice,
            'line_total_value' => $unitPrice * $qty,
        ];
    }

    /**
     * Configure the PDO mock to allow any number of successful transactions.
     * inTransaction() returns true so the service's commit() call is reached.
     */
    private function withPdoTransactions(): void
    {
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('inTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
    }

    // -------------------------------------------------------------------------
    // Planner-state guard tests
    // -------------------------------------------------------------------------

    public function test_empty_planner_returns_planner_empty_status(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn(null);
        $this->planner->method('getDetailedPlanner')
            ->willReturn($this->plannerPayload(isEmpty: true, hasInvalidItems: false));

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('planner_empty', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_planner_with_invalid_items_returns_planner_invalid_status(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn(null);
        $this->planner->method('getDetailedPlanner')
            ->willReturn($this->plannerPayload(isEmpty: false, hasInvalidItems: true));

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('planner_invalid', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    // -------------------------------------------------------------------------
    // Existing-attempt resolution tests
    // -------------------------------------------------------------------------

    public function test_existing_handoff_created_attempt_returns_already_pending(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn([
            'checkout_attempt_id' => 42,
            'status'              => 'handoff_created',
        ]);
        $this->planner->expects($this->once())->method('lock')->with(42);

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('already_pending', $result['status']);
        $this->assertSame(42, $result['attempt_id']);
        $this->assertStringContainsString('/checkout/pending/42', $result['redirect_url']);
    }

    public function test_existing_paid_attempt_returns_already_paid(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn([
            'checkout_attempt_id' => 7,
            'status'              => 'paid',
        ]);

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('already_paid', $result['status']);
        $this->assertSame(7, $result['attempt_id']);
    }

    // -------------------------------------------------------------------------
    // Locked-planner guard test
    // -------------------------------------------------------------------------

    public function test_locked_planner_returns_locked_status_with_attempt_id(): void
    {
        $this->planner->method('isLocked')->willReturn(true);
        $this->planner->method('getLockedCheckoutAttemptId')->willReturn(99);

        $result = $this->sut->confirmCheckout($this->user, 'any-key');

        $this->assertSame('locked', $result['status']);
        $this->assertSame(99, $result['attempt_id']);
        $this->assertNotEmpty($result['message']);
    }

    // -------------------------------------------------------------------------
    // Idempotency-key validation test
    // -------------------------------------------------------------------------

    public function test_mismatched_idempotency_key_returns_invalid_request(): void
    {
        $this->planner->method('isLocked')->willReturn(false);
        // Server holds 'server-key'; client posts a different value.
        $this->planner->method('getIdempotencyKey')->willReturn('server-key');

        $result = $this->sut->confirmCheckout($this->user, 'wrong-key');

        $this->assertSame('invalid_request', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    // -------------------------------------------------------------------------
    // Retry-required tests (handoff_failed / expired)
    // -------------------------------------------------------------------------

    public function test_handoff_failed_attempt_returns_retry_required_and_rotates_key(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn([
            'checkout_attempt_id' => 55,
            'status'              => 'handoff_failed',
        ]);
        // Key must be rotated exactly once so the user can retry.
        $this->planner->expects($this->once())->method('rotateIdempotencyKey');

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('retry_required', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_expired_attempt_returns_retry_required_and_rotates_key(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn([
            'checkout_attempt_id' => 77,
            'status'              => 'expired',
        ]);
        $this->planner->expects($this->once())->method('rotateIdempotencyKey');

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('retry_required', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    // -------------------------------------------------------------------------
    // Handoff path tests
    // -------------------------------------------------------------------------

    /**
     * Happy path: gateway accepts the transaction.
     *
     * Expected side-effects:
     *   - a checkout attempt row is created (createAttempt called)
     *   - the planner is locked with the new attempt ID
     *   - the idempotency key is rotated so the same form submit cannot re-fire
     *
     * Expected return: status=handoff_created with an attempt_id and redirect_url.
     */
    public function test_successful_handoff_returns_handoff_created_locks_planner_and_rotates_key(): void
    {
        // Unlocked planner, key matches, no prior attempt for this key.
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn(null);

        // Valid planner with one purchasable item.
        $this->planner->method('getDetailedPlanner')
            ->willReturn($this->plannerPayload(isEmpty: false, hasInvalidItems: false, items: [
                $this->validPlannerItem(eventId: 1, name: 'Jazz Night', qty: 2, unitPrice: 15.00),
            ]));
        $this->planner->method('getPlannerToken')->willReturn('planner-token-abc');

        // Stock reservation succeeds; createAttempt returns a new ID.
        $this->events->method('decrementTicketAmountIfAvailable')->willReturn(true);
        $this->checkoutAttempts->method('createAttempt')->willReturn(42);

        // Two DB transactions run: createPendingCheckoutAttempt + markAttemptAsHandedOff.
        $this->withPdoTransactions();

        // Payment gateway creates the transaction successfully.
        $this->paymentGateway->method('createTransaction')->willReturn([
            'success'            => true,
            'redirect_url'       => '/pay/stub',
            'provider_reference' => 'ref-xyz',
        ]);

        // Key assertions: planner must be locked with the created attempt ID
        // and the idempotency key must be rotated before the response is returned.
        $this->planner->expects($this->once())->method('lock')->with(42);
        $this->planner->expects($this->once())->method('rotateIdempotencyKey');

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('handoff_created', $result['status']);
        $this->assertSame(42, $result['attempt_id']);
        $this->assertNotEmpty($result['redirect_url']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Sad path: gateway rejects the transaction.
     *
     * Expected side-effects:
     *   - the planner is unlocked (so the user can retry)
     *   - the idempotency key is rotated (so the retry form gets a fresh key)
     *
     * Expected return: status=handoff_failed with the gateway error message.
     */
    public function test_failed_handoff_returns_handoff_failed_unlocks_planner_and_rotates_key(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn(null);

        $this->planner->method('getDetailedPlanner')
            ->willReturn($this->plannerPayload(isEmpty: false, hasInvalidItems: false, items: [
                $this->validPlannerItem(eventId: 1, name: 'Jazz Night', qty: 2, unitPrice: 15.00),
            ]));
        $this->planner->method('getPlannerToken')->willReturn('planner-token-abc');

        $this->events->method('decrementTicketAmountIfAvailable')->willReturn(true);
        $this->checkoutAttempts->method('createAttempt')->willReturn(42);

        // Two DB transactions: createPendingCheckoutAttempt + handleFailedHandoff cleanup.
        $this->withPdoTransactions();

        // findByAttemptForUpdate returns no holds → releaseAttemptHoldsAndRestoreStock exits early.
        $this->ticketHolds->method('findByAttemptForUpdate')->willReturn([]);

        // Gateway rejects the transaction.
        $this->paymentGateway->method('createTransaction')->willReturn([
            'success'       => false,
            'error_code'    => 'GW_TIMEOUT',
            'error_message' => 'Gateway timed out.',
        ]);

        // Key assertions: planner must be unlocked and key rotated so the user can retry.
        $this->planner->expects($this->once())->method('unlock');
        $this->planner->expects($this->once())->method('rotateIdempotencyKey');

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('handoff_failed', $result['status']);
        $this->assertSame(42, $result['attempt_id']);
        $this->assertStringContainsString('Gateway timed out.', $result['message']);
    }

    // -------------------------------------------------------------------------
    // confirmPendingPayment() branch tests
    // -------------------------------------------------------------------------

    /**
     * Build a PDO-transaction-aware findByIdForUpdate stub for confirmPendingPayment tests.
     *
     * @param int    $attemptUserId  user_id stored on the attempt row
     * @param string $status         attempt status
     * @param int    $attemptId      checkout_attempt_id returned
     * @param string $holdExpiresAt  far-future timestamp so expiry branch is not triggered
     */
    private function withAttemptFound(
        int    $attemptUserId,
        string $status,
        int    $attemptId = 42,
        string $holdExpiresAt = '2099-12-31 23:59:59'
    ): void {
        $this->withPdoTransactions();
        $this->checkoutAttempts->method('findByIdForUpdate')->willReturn([
            'checkout_attempt_id' => $attemptId,
            'user_id'             => $attemptUserId,
            'status'              => $status,
            'total_price'         => 30.00,
            'currency'            => 'EUR',
            'hold_expires_at'     => $holdExpiresAt,
        ]);
    }

    /**
     * A checkoutAttemptId of 0 (or any non-positive value) is rejected before
     * any DB call is made.
     */
    public function test_confirm_pending_payment_invalid_attempt_id_returns_not_found(): void
    {
        $result = $this->sut->confirmPendingPayment(0, $this->user);

        $this->assertSame('not_found', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * A User whose id() returns 0 (unauthenticated / guest) is forbidden before
     * any DB call is made.
     */
    public function test_confirm_pending_payment_invalid_user_id_returns_forbidden(): void
    {
        $guestUser = User::fromArray(['user_id' => 0, 'username' => 'guest', 'email' => 'g@example.com']);

        $result = $this->sut->confirmPendingPayment(42, $guestUser);

        $this->assertSame('forbidden', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * When the attempt row's user_id does not match the authenticated user's id,
     * the service must deny access even though the attempt exists.
     */
    public function test_confirm_pending_payment_attempt_owned_by_other_user_returns_forbidden(): void
    {
        // Attempt belongs to user 99; our user is user 1.
        $this->withAttemptFound(attemptUserId: 99, status: 'handoff_created');

        $result = $this->sut->confirmPendingPayment(42, $this->user);

        $this->assertSame('forbidden', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * An attempt already in status 'paid' must return already_paid so the
     * caller can redirect without creating a duplicate invoice.
     */
    public function test_confirm_pending_payment_already_paid_attempt_returns_already_paid(): void
    {
        $this->withAttemptFound(attemptUserId: 1, status: 'paid');

        $result = $this->sut->confirmPendingPayment(42, $this->user);

        $this->assertSame('already_paid', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * An attempt whose status is 'expired' (set by a previous cleanup run)
     * returns expired so the UI can prompt the user to start over.
     */
    public function test_confirm_pending_payment_expired_status_returns_expired(): void
    {
        $this->withAttemptFound(attemptUserId: 1, status: 'expired');

        $result = $this->sut->confirmPendingPayment(42, $this->user);

        $this->assertSame('expired', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Any unrecognised / unexpected status (e.g. 'initiated') is rejected with
     * invalid_status so we never accidentally double-process an attempt.
     */
    public function test_confirm_pending_payment_unrecognised_status_returns_invalid_status(): void
    {
        $this->withAttemptFound(attemptUserId: 1, status: 'initiated');

        $result = $this->sut->confirmPendingPayment(42, $this->user);

        $this->assertSame('invalid_status', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Happy path: attempt is in 'handoff_created' status and belongs to the
     * authenticated user.
     *
     * Expected side-effects (all verified as called exactly once):
     *   - createInvoice($userId, $totalPrice)
     *   - createTicketsForAttempt($attemptId, $userId, $invoiceId)
     *   - markPaidByAttemptId($attemptId, <datetime string>)
     *   - markPaid($attemptId)
     *
     * Expected return: status=paid, invoice_id matches, email_warning is null.
     */
    public function test_successful_confirm_pending_payment_returns_paid_and_calls_core_repos(): void
    {
        // Attempt belongs to user 1, is awaiting confirmation, hold is far in the future.
        $this->withAttemptFound(attemptUserId: 1, status: 'handoff_created', attemptId: 42);

        // Core write calls — all must be triggered exactly once with the right args.
        $this->checkoutAttempts->expects($this->once())
            ->method('createInvoice')
            ->with(1, 30.00)        // user_id=1, total_price=30.00 from the stub row
            ->willReturn(101);

        $this->checkoutAttempts->expects($this->once())
            ->method('createTicketsForAttempt')
            ->with(42, 1, 101)      // attempt_id, user_id, invoice_id
            ->willReturn([]);       // no tickets keeps buildDeliverableTickets simple

        $this->ticketHolds->expects($this->once())
            ->method('markPaidByAttemptId')
            ->with(42, $this->isType('string')); // second arg is a formatted datetime

        $this->checkoutAttempts->expects($this->once())
            ->method('markPaid')
            ->with(42);

        // Email delivery is allowed to succeed silently; no assertion needed here.

        $result = $this->sut->confirmPendingPayment(42, $this->user);

        $this->assertSame('paid', $result['status']);
        $this->assertSame(101, $result['invoice_id']);
        $this->assertNull($result['email_warning']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * If ticket-email or invoice-email delivery throws, the payment is already
     * persisted (paid), so the service must still return status=paid and surface
     * the failure through email_warning rather than re-raising.
     */
    public function test_confirm_pending_payment_email_failure_still_returns_paid_with_warning(): void
    {
        $this->withAttemptFound(attemptUserId: 1, status: 'handoff_created', attemptId: 42);

        $this->checkoutAttempts->method('createInvoice')->willReturn(101);
        $this->checkoutAttempts->method('createTicketsForAttempt')->willReturn([]);

        // Both email calls throw — the service must catch both and continue.
        $this->ticketDelivery->method('sendPurchasedTicketsEmail')
            ->willThrowException(new \RuntimeException('SMTP offline'));
        $this->ticketDelivery->method('sendInvoiceEmail')
            ->willThrowException(new \RuntimeException('SMTP offline'));

        $result = $this->sut->confirmPendingPayment(42, $this->user);

        $this->assertSame('paid', $result['status']);
        $this->assertSame(101, $result['invoice_id']);
        $this->assertNotNull($result['email_warning']);
        // Warning message must mention both delivery failures.
        $this->assertStringContainsString('ticket email delivery failed', $result['email_warning']);
        $this->assertStringContainsString('invoice email delivery failed', $result['email_warning']);
    }

    // -------------------------------------------------------------------------
    // Stock-exhausted tests
    // -------------------------------------------------------------------------

    /**
     * Stock exhausted path: decrementTicketAmountIfAvailable returns false for at
     * least one item, so the service rolls back, rebuilds a conflict list, and
     * returns without touching the planner lock or rotating the key.
     *
     * Expected return: status=out_of_stock with a non-empty conflicts array that
     * describes which events were over-requested.
     */
    public function test_out_of_stock_returns_out_of_stock_status_with_conflicts(): void
    {
        $this->withUnlockedPlanner();
        $this->checkoutAttempts->method('findByIdempotencyKey')->willReturn(null);

        $this->planner->method('getDetailedPlanner')
            ->willReturn($this->plannerPayload(isEmpty: false, hasInvalidItems: false, items: [
                $this->validPlannerItem(eventId: 7, name: 'Yolanda Jazz', qty: 3, unitPrice: 20.00),
            ]));
        $this->planner->method('getPlannerToken')->willReturn('planner-token-abc');

        // Stock reservation fails → the service calls pdo->rollBack() explicitly.
        $this->events->method('decrementTicketAmountIfAvailable')->willReturn(false);

        // After the explicit rollBack the service checks inTransaction(); returning
        // false prevents the transaction() wrapper from calling commit() again.
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('rollBack')->willReturn(true);
        $this->pdo->method('inTransaction')->willReturn(false);

        // findStockByIds is called to build the human-readable conflict list.
        $this->events->method('findStockByIds')->willReturn([
            7 => ['name' => 'Yolanda Jazz', 'ticket_amount' => 1],
        ]);

        // The planner must NOT be locked or have its key rotated on this path.
        $this->planner->expects($this->never())->method('lock');
        $this->planner->expects($this->never())->method('rotateIdempotencyKey');

        $result = $this->sut->confirmCheckout($this->user, 'test-key');

        $this->assertSame('out_of_stock', $result['status']);
        $this->assertNotEmpty($result['message']);
        $this->assertIsArray($result['conflicts']);
        $this->assertCount(1, $result['conflicts']);

        $conflict = $result['conflicts'][0];
        $this->assertSame(7, $conflict['event_id']);
        $this->assertSame(3, $conflict['requested']);
        $this->assertSame(1, $conflict['available']);
    }
}
