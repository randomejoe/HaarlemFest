<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Models\CheckoutResult;
use App\Models\User;
use App\Repositories\CheckoutRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketHoldRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\CheckoutHoldManager;
use App\Services\CheckoutService;
use App\Services\DateTimeFormatter;
use App\Services\PaymentGatewayStubService;
use App\Services\PaymentHandoffService;
use App\Services\PlannerService;
use App\Services\SessionManager;
use App\Services\StockReservationService;
use App\Services\Interfaces\ITicketDeliveryService;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutServiceTest extends TestCase
{
    private PDO&MockObject $pdo;
    private PlannerService&MockObject $planner;
    private CheckoutRepository&MockObject $checkoutAttempts;
    private TicketHoldRepository&MockObject $ticketHolds;
    private EventRepository&MockObject $events;
    private PaymentGatewayStubService&MockObject $paymentGateway;
    private ITicketDeliveryService&MockObject $ticketDelivery;
    private IUserRepository&MockObject $users;

    private CheckoutService $sut;
    private User $user;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->planner = $this->createMock(PlannerService::class);
        $this->checkoutAttempts = $this->createMock(CheckoutRepository::class);
        $this->ticketHolds = $this->createMock(TicketHoldRepository::class);
        $this->events = $this->createMock(EventRepository::class);
        $this->paymentGateway = $this->createMock(PaymentGatewayStubService::class);
        $this->ticketDelivery = $this->createMock(ITicketDeliveryService::class);
        $this->users = $this->createMock(IUserRepository::class);

        $dateTimeFormatter = new DateTimeFormatter();

        $stockReservation = new StockReservationService(
            $this->events,
            $this->ticketHolds,
            $dateTimeFormatter
        );

        $holdManager = new CheckoutHoldManager(
            $this->ticketHolds,
            $this->checkoutAttempts,
            $this->events,
            $dateTimeFormatter,
            $this->pdo
        );

        $handoffService = new PaymentHandoffService($this->paymentGateway);

        $this->sut = new CheckoutService(
            $this->pdo,
            $this->planner,
            $this->checkoutAttempts,
            $holdManager,
            $dateTimeFormatter,
            $stockReservation,
            $handoffService,
            $this->ticketDelivery,
            $this->users
        );

        $this->user = User::fromArray([
            'user_id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'address' => 'Test Street 1',
            'city' => 'Haarlem',
            'country' => 'NL',
            'phone_number' => '+31 555 0101',
        ]);
    }

    private function validPlannerPayload(float $totalPrice = 30.0): array
    {
        return [
            'is_empty' => false,
            'has_invalid_items' => false,
            'items' => [
                [
                    'event_id' => 1,
                    'name' => 'Jazz Night',
                    'quantity' => 2,
                    'is_valid' => true,
                    'unit_price_value' => 15.00,
                    'line_total_value' => 30.00,
                ],
            ],
            'total_price_value' => $totalPrice,
        ];
    }

    public function test_confirmCheckout_existing_handoff_created_locks_with_attempt_hold_expires_at(): void
    {
        $this->planner->method('isLocked')->willReturn(false);
        $this->planner->method('getIdempotencyKey')->willReturn('test-key');

        $attemptId = 42;
        $holdExpiresAt = '2099-12-31 23:59:59';

        $this->checkoutAttempts->method('findByIdempotencyKey')->with('test-key')->willReturn([
            'checkout_attempt_id' => $attemptId,
            'status' => 'handoff_created',
            'hold_expires_at' => $holdExpiresAt,
        ]);

        $this->planner->expects($this->once())
            ->method('lock')
            ->with($attemptId, $holdExpiresAt);

        $result = $this->sut->confirmCheckout($this->user, 'test-key')->toArray();

        $this->assertSame('already_pending', $result['status']);
        $this->assertSame($attemptId, $result['attempt_id']);
        $this->assertSame('/checkout/pending/' . $attemptId, $result['redirect_url']);
    }

    public function test_confirmCheckout_successful_handoff_passes_hold_expires_at_into_planner_lock(): void
    {
        $this->planner->method('isLocked')->willReturn(false);
        $this->planner->method('getIdempotencyKey')->willReturn('test-key');
        $this->checkoutAttempts->method('findByIdempotencyKey')->with('test-key')->willReturn(null);
        $this->planner->method('getDetailedPlanner')->willReturn($this->validPlannerPayload(30.0));
        $this->planner->expects($this->once())->method('resetExpiryCleanupRun');
        $this->planner->method('getPlannerToken')->willReturn('planner-token-abc');

        $this->events->method('decrementTicketAmountIfAvailable')->willReturn(true);

        $attemptId = 42;
        $capturedHoldExpiresAt = null;

        $this->pdo->expects($this->exactly(2))->method('beginTransaction')->willReturn(true);
        $this->pdo->method('inTransaction')->willReturn(true);
        $this->pdo->expects($this->exactly(2))->method('commit')->willReturn(true);

        $this->checkoutAttempts->expects($this->once())
            ->method('createAttempt')
            ->with($this->callback(function (array $data) use (&$capturedHoldExpiresAt): bool {
                $capturedHoldExpiresAt = (string) ($data['hold_expires_at'] ?? '');
                return $capturedHoldExpiresAt !== '';
            }))
            ->willReturn($attemptId);

        $this->checkoutAttempts->expects($this->once())
            ->method('createAttemptItems')
            ->with($attemptId, $this->isType('array'));

        $this->ticketHolds->expects($this->once())
            ->method('createHolds')
            ->with(
                $attemptId,
                $this->user->getId(),
                'planner-token-abc',
                $this->isType('array'),
                $this->callback(function (string $expiresAt) use (&$capturedHoldExpiresAt): bool {
                    return $capturedHoldExpiresAt !== null && $expiresAt === $capturedHoldExpiresAt;
                })
            );

        $this->paymentGateway->expects($this->once())
            ->method('createTransaction')
            ->with($this->isType('array'))
            ->willReturn([
                'success' => true,
                'redirect_url' => '/checkout/pending/' . $attemptId,
                'provider_reference' => 'ref-xyz',
                'error_code' => null,
                'error_message' => null,
            ]);

        $this->checkoutAttempts->expects($this->once())
            ->method('markHandoffCreated')
            ->with($attemptId, 'stub_provider', 'ref-xyz');

        $this->planner->expects($this->once())
            ->method('lock')
            ->with(
                $attemptId,
                $this->callback(function (string $expiresAt) use (&$capturedHoldExpiresAt): bool {
                    return $capturedHoldExpiresAt !== null && $expiresAt === $capturedHoldExpiresAt;
                })
            );

        $this->planner->expects($this->once())
            ->method('rotateIdempotencyKey');

        $this->ticketHolds->expects($this->once())
            ->method('markTransferredByAttemptId')
            ->with($attemptId);

        $this->ticketDelivery->expects($this->never())
            ->method('deliverPurchaseEmails');

        $result = $this->sut->confirmCheckout($this->user, 'test-key')->toArray();

        $this->assertSame('handoff_created', $result['status']);
        $this->assertSame($attemptId, $result['attempt_id']);
        $this->assertSame('/checkout/pending/' . $attemptId, $result['redirect_url']);
    }
}

class PlannerServiceLockTtlTest extends TestCase
{
    private PlannerService $planner;

    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        $events = $this->createMock(EventRepository::class);
        $session = new SessionManager();
        $this->planner = new PlannerService($events, $session);
    }

    public function test_lock_auto_unlocks_after_hold_expires_at(): void
    {
        $attemptId = 123;
        $holdExpiresAt = date('Y-m-d H:i:s', time() - 1);

        $this->planner->lock($attemptId, $holdExpiresAt);

        $this->assertFalse($this->planner->isLocked());
        $this->assertNull($this->planner->getLockedCheckoutAttemptId());
        $this->assertNull($_SESSION['planner']['locked_checkout_attempt_id'] ?? null);
    }

    public function test_unlockIfAttemptId_does_not_clobber_other_attempts(): void
    {
        $attemptId = 1;
        $this->planner->lock($attemptId, date('Y-m-d H:i:s', time() + 3600));

        $this->planner->unlockIfAttemptId(2);
        $this->assertTrue($this->planner->isLocked());
        $this->assertSame($attemptId, $this->planner->getLockedCheckoutAttemptId());

        $this->planner->unlockIfAttemptId($attemptId);
        $this->assertFalse($this->planner->isLocked());
        $this->assertNull($this->planner->getLockedCheckoutAttemptId());
    }
}
