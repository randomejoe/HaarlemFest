<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Models\User;
use App\Repositories\Interfaces\ICheckoutRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\CheckoutService;
use App\Services\Interfaces\IPlannerService;
use App\Services\Interfaces\ITicketDeliveryService;
use App\Services\Interfaces\ITransactionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CheckoutServiceTest extends TestCase
{
    private ITransactionManager&MockObject $txManager;
    private IPlannerService&MockObject $planner;
    private ICheckoutRepository&MockObject $checkoutRepo;
    private IUserRepository&MockObject $users;
    private ITicketDeliveryService&MockObject $ticketDelivery;
    private CheckoutService $sut;
    private User $user;

    protected function setUp(): void
    {
        $this->txManager = $this->createMock(ITransactionManager::class);
        $this->planner = $this->createMock(IPlannerService::class);
        $this->checkoutRepo = $this->createMock(ICheckoutRepository::class);
        $this->users = $this->createMock(IUserRepository::class);
        $this->ticketDelivery = $this->createMock(ITicketDeliveryService::class);
        $this->sut = new CheckoutService(
            $this->txManager,
            $this->planner,
            $this->checkoutRepo,
            $this->users,
            $this->ticketDelivery
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

    public function test_confirmCheckout_creates_invoice_tickets_and_clears_planner(): void
    {
        $this->planner->method('getDetailedPlanner')->willReturn([
            'is_empty' => false,
            'items' => [
                [
                    'event_id' => 5,
                    'quantity' => 2,
                    'is_valid' => true,
                    'unit_price_value' => 12.5,
                ],
            ],
            'total_price_value' => 25.0,
        ]);

        $this->txManager->expects($this->once())->method('run')->willReturnCallback(fn(callable $op) => $op());

        $this->checkoutRepo->expects($this->once())
            ->method('findEventForUpdate')
            ->with(5)
            ->willReturn([
                'event_id' => 5,
                'name' => 'Jazz Night',
                'available_tickets' => 10,
                'ticket_price' => 12.5,
            ]);

        $this->checkoutRepo->expects($this->once())
            ->method('createInvoice')
            ->with($this->user->getId(), 25.0)
            ->willReturn(99);

        $this->checkoutRepo->expects($this->exactly(2))
            ->method('createTicket')
            ->with(99, $this->user->getId(), 5, 12.5);

        $this->checkoutRepo->expects($this->once())
            ->method('decrementStock')
            ->with(5, 2);

        $this->checkoutRepo->expects($this->once())
            ->method('findInvoiceById')
            ->with(99)
            ->willReturn([
                'invoice_id' => 99,
                'user_id' => $this->user->getId(),
                'total_price' => 25.0,
                'issued_at' => '2026-04-29 10:00:00',
                'invoice_number' => 'INV-99',
                'currency' => 'EUR',
            ]);

        $this->checkoutRepo->expects($this->once())
            ->method('findTicketsByInvoiceId')
            ->with(99)
            ->willReturn([
                [
                    'ticket_id' => 1,
                    'event_id' => 5,
                    'ticket_price' => 12.5,
                    'event_name' => 'Jazz Night',
                    'event_date' => '2026-07-30',
                    'event_time' => '20:00',
                    'venue' => 'Main Hall',
                    'verification_code' => 'ABC123',
                ],
                [
                    'ticket_id' => 2,
                    'event_id' => 5,
                    'ticket_price' => 12.5,
                    'event_name' => 'Jazz Night',
                    'event_date' => '2026-07-30',
                    'event_time' => '20:00',
                    'venue' => 'Main Hall',
                    'verification_code' => 'DEF456',
                ],
            ]);

        $this->ticketDelivery->expects($this->once())
            ->method('sendOrderConfirmation')
            ->with(
                $this->user,
                99,
                $this->callback(fn(array $tickets): bool => count($tickets) === 2),
                25.0
            );

        $this->planner->expects($this->once())->method('clear');

        $result = $this->sut->confirmCheckout($this->user);

        $this->assertSame(['success' => true, 'order_id' => 99], $result);
    }

    public function test_confirmCheckout_rejects_empty_planner(): void
    {
        $this->planner->method('getDetailedPlanner')->willReturn(['is_empty' => true]);
        $this->txManager->expects($this->never())->method('run');

        $result = $this->sut->confirmCheckout($this->user);

        $this->assertFalse($result['success']);
        $this->assertSame('Your planner is empty.', $result['message']);
    }
}
