<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use App\Controllers\CheckoutController;
use App\Models\Event;
use App\Models\PlannerSummary;
use App\Models\User;
use App\Services\AuthService;
use App\Services\Interfaces\ICheckoutService;
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

    private function makeNonEmptySummary(): PlannerSummary
    {
        $event = new Event(3, 'Jazz Night', null, '2026-07-01 20:00:00', '2026-07-01 22:00:00', 18.0, 100, null, 'jazz', 'Venue', null, null);
        return PlannerSummary::fromRawItems([3 => ['quantity' => 1, 'familyTicket' => false]], [3 => $event]);
    }

    public function test_show_renders_checkout_contact_form_when_details_are_missing(): void
    {
        $this->auth->expects($this->once())->method('currentUser')->willReturn($this->user);
        $this->checkout->expects($this->once())->method('loadCheckoutUser')->with($this->user->getId())->willReturn($this->user);
        $this->checkout->expects($this->once())->method('getPlannerSummary')->willReturn($this->makeNonEmptySummary());
        $this->checkout->expects($this->once())->method('missingCheckoutDetails')->with($this->user)->willReturn(['address']);
        $this->checkout->expects($this->once())->method('consumeFlash')->willReturn(null);

        ob_start();
        (new CheckoutController($this->checkout, $this->auth))->show();
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('Your contact details', $output);
        $this->assertStringContainsString('Save details', $output);
    }

    public function test_show_renders_optional_mock_payment_fields_when_details_are_complete(): void
    {
        $this->auth->expects($this->once())->method('currentUser')->willReturn($this->user);
        $this->checkout->expects($this->once())->method('loadCheckoutUser')->with($this->user->getId())->willReturn($this->user);
        $this->checkout->expects($this->once())->method('getPlannerSummary')->willReturn($this->makeNonEmptySummary());
        $this->checkout->expects($this->once())->method('missingCheckoutDetails')->with($this->user)->willReturn([]);
        $this->checkout->expects($this->once())->method('consumeFlash')->willReturn(null);

        ob_start();
        (new CheckoutController($this->checkout, $this->auth))->show();
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('Name on card', $output);
        $this->assertStringContainsString('name="card_number"', $output);
        $this->assertStringContainsString('name="card_expiry"', $output);
        $this->assertStringContainsString('name="card_cvc"', $output);
        $this->assertStringContainsString('Place order', $output);
    }

    public function test_confirm_redirects_to_orders_when_checkout_succeeds(): void
    {
        $this->auth->expects($this->once())->method('currentUser')->willReturn($this->user);
        $this->checkout->expects($this->once())
            ->method('loadCheckoutUser')
            ->with($this->user->getId())
            ->willReturn($this->user);
        $this->checkout->expects($this->once())
            ->method('confirmCheckout')
            ->with($this->user)
            ->willReturn(['success' => true, 'order_id' => 88]);
        $this->checkout->expects($this->once())
            ->method('setFlash')
            ->with(\App\Models\FlashType::Success, 'Thank you! Your order has been placed.');

        $controller = new class($this->checkout, $this->auth) extends CheckoutController {
            protected function redirect(string $location): void
            {
                throw new RedirectIntercept($location);
            }
        };

        $_POST = [
            'card_name' => 'Any Name',
            'card_number' => 'not-a-real-card',
            'card_expiry' => '',
            'card_cvc' => '',
        ];

        $this->expectException(RedirectIntercept::class);

        try {
            $controller->confirm();
        } catch (RedirectIntercept $e) {
            $this->assertSame('/orders', $e->location);
            throw $e;
        }
    }
}

final class RedirectIntercept extends RuntimeException
{
    public function __construct(public string $location)
    {
        parent::__construct('Redirect to ' . $location);
    }
}
