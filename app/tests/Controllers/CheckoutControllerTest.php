<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use App\Controllers\CheckoutController;
use App\Models\User;
use App\Services\AuthService;
use App\Services\Interfaces\ICheckoutService;
use App\Models\CheckoutResult;
use App\Models\HoldExpiryResult;
use App\Models\PaymentConfirmationResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CheckoutControllerTest extends TestCase
{
    private ICheckoutService&MockObject $checkout;
    private AuthService&MockObject $auth;
    private User $user;

    protected function setUp(): void
    {
        $this->checkout = $this->createMock(ICheckoutService::class);
        $this->auth = $this->createMock(AuthService::class);
        $this->user = User::fromArray([
            'user_id' => 7,
            'username' => 'alice',
            'email' => 'alice@example.com',
            'first_name' => 'Alice',
            'last_name' => 'Example',
            'address' => 'Main Street 1',
            'city' => 'Haarlem',
            'country' => 'NL',
            'phone_number' => '+31 555 0100',
        ]);

        $_SESSION = [];
        $_POST = [];
    }

    public function test_show_renders_checkout_contact_form_when_details_are_missing(): void
    {
        $this->checkout->expects($this->once())
            ->method('releaseExpiredHoldsIfNeeded')
            ->with(false)
            ->willReturn(new HoldExpiryResult(0, []));

        $this->checkout->expects($this->once())
            ->method('getLockedAttemptId')
            ->willReturn(null);

        $this->auth->expects($this->once())
            ->method('currentUser')
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('loadCheckoutUser')
            ->with($this->user->getId())
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('buildCheckoutView')
            ->with($this->user)
            ->willReturn([
                'planner' => [
                    'total_quantity' => 1,
                    'total_price' => '18.00',
                    'is_locked' => false,
                    'is_empty' => false,
                    'has_invalid_items' => false,
                    'invalid_item_ids' => [],
                    'time_conflicts' => [],
                    'time_conflict_pairs' => [],
                    'items' => [],
                ],
                'user' => $this->user,
                'flash' => null,
                'missing_fields' => ['address'],
                'requires_details' => true,
                'idempotency_key' => 'abc123',
            ]);

        ob_start();
        (new CheckoutController($this->checkout, $this->auth))->show();
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('Your contact details', $output);
        $this->assertStringContainsString('Continue to payment', $output);
    }

    public function test_saveDetails_persists_trimmed_required_fields_and_redirects_to_checkout(): void
    {
        $this->auth->expects($this->once())
            ->method('currentUser')
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('loadCheckoutUser')
            ->with($this->user->getId())
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('saveCheckoutDetails')
            ->with($this->user->getId(), [
                'first_name' => 'Alice',
                'last_name' => 'Example',
                'address' => 'Main Street 1',
                'city' => 'Haarlem',
                'country' => 'NL',
                'phone_number' => '+31 555 0100',
            ]);

        $this->checkout->expects($this->once())
            ->method('setFlash')
            ->with('success', 'Your checkout details were saved.');

        $_POST = [
            'first_name' => ' Alice ',
            'last_name' => ' Example ',
            'address' => ' Main Street 1 ',
            'city' => ' Haarlem ',
            'country' => ' NL ',
            'phone_number' => ' +31 555 0100 ',
        ];

        $controller = new class($this->checkout, $this->auth) extends CheckoutController {
            public ?string $redirectLocation = null;

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
                throw new RedirectIntercept($location);
            }
        };

        $this->expectException(RedirectIntercept::class);

        try {
            $controller->saveDetails();
        } catch (RedirectIntercept $e) {
            $this->assertSame('/checkout', $e->location);
            throw $e;
        }
    }

    public function test_confirm_redirects_to_pending_attempt_when_handoff_is_created(): void
    {
        $this->checkout->expects($this->once())
            ->method('releaseExpiredHoldsIfNeeded')
            ->with(true)
            ->willReturn(new HoldExpiryResult(0, []));

        $this->checkout->expects($this->once())
            ->method('getLockedAttemptId')
            ->willReturn(null);

        $this->auth->expects($this->once())
            ->method('currentUser')
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('loadCheckoutUser')
            ->with($this->user->getId())
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('missingCheckoutDetails')
            ->with($this->user)
            ->willReturn([]);

        $this->checkout->expects($this->once())
            ->method('confirmCheckout')
            ->with($this->user, 'idempotency-key')
            ->willReturn(new CheckoutResult(
                'handoff_created',
                'Payment handoff created.',
                88,
                '/checkout/pending/88'
            ));

        $controller = new class($this->checkout, $this->auth) extends CheckoutController {
            public ?string $redirectLocation = null;

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
                throw new RedirectIntercept($location);
            }
        };

        $_POST = ['idempotency_key' => 'idempotency-key'];

        $this->expectException(RedirectIntercept::class);

        try {
            $controller->confirm();
        } catch (RedirectIntercept $e) {
            $this->assertSame('/checkout/pending/88', $e->location);
            throw $e;
        }
    }

    public function test_pending_renders_checkout_pending_view_for_owned_attempt(): void
    {
        $this->checkout->expects($this->once())
            ->method('releaseExpiredHoldsIfNeeded')
            ->with(true)
            ->willReturn(new HoldExpiryResult(0, []));

        $this->auth->expects($this->once())
            ->method('currentUser')
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('buildPendingView')
            ->with(55, $this->user)
            ->willReturn([
                'attempt' => [
                    'checkout_attempt_id' => 55,
                    'user_id' => $this->user->getId(),
                    'status' => 'handoff_created',
                    'hold_expires_at' => '2099-12-31 23:59:59',
                    'total_price' => 25.0,
                ],
                'items' => [
                    ['name' => 'Jazz Night', 'quantity' => 2, 'start_time' => '2026-01-01 18:00:00', 'end_time' => '2026-01-01 20:00:00'],
                ],
                'flash' => null,
                'user' => $this->user,
            ]);

        ob_start();
        (new CheckoutController($this->checkout, $this->auth))->pending(55);
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('Payment pending', $output);
        $this->assertStringContainsString('Jazz Night', $output);
    }

    public function test_confirmPendingPayment_clears_planner_after_paid_confirmation(): void
    {
        $this->checkout->expects($this->once())
            ->method('releaseExpiredHoldsIfNeeded')
            ->with(true)
            ->willReturn(new HoldExpiryResult(0, []));

        $this->auth->expects($this->once())
            ->method('currentUser')
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('loadCheckoutUser')
            ->with($this->user->getId())
            ->willReturn($this->user);

        $this->checkout->expects($this->once())
            ->method('confirmPendingPayment')
            ->with(77, $this->user)
            ->willReturn(new PaymentConfirmationResult(
                'paid',
                'Payment confirmed.',
                99
            ));

        $this->checkout->expects($this->once())
            ->method('unlockIfAttemptId')
            ->with(77);

        $this->checkout->expects($this->once())
            ->method('clearPlannerIfUnlocked');

        $this->checkout->expects($this->once())
            ->method('setFlash')
            ->with('success', 'Payment confirmed.');

        $controller = new class($this->checkout, $this->auth) extends CheckoutController {
            public ?string $redirectLocation = null;

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
                throw new RedirectIntercept($location);
            }
        };

        $this->expectException(RedirectIntercept::class);

        try {
            $controller->confirmPendingPayment(77);
        } catch (RedirectIntercept $e) {
            $this->assertSame('/checkout/pending/77', $e->location);
            throw $e;
        }
    }
}

final class RedirectIntercept extends RuntimeException
{
    public function __construct(
        public string $location
    ) {
        parent::__construct($location);
    }
}
