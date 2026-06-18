<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\PlannerSummary;
use App\Repositories\Interfaces\ICheckoutRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\Interfaces\ICheckoutService;
use App\Services\Interfaces\IPlannerService;
use App\Services\Interfaces\ITicketDeliveryService;
use App\Services\Interfaces\ITransactionManager;
use Throwable;

final class CheckoutService implements ICheckoutService
{
    private const REQUIRED_FIELDS = ['first_name', 'last_name', 'address', 'city', 'country', 'phone_number'];

    private const FIELD_GETTERS = [
        'first_name'   => 'firstName',
        'last_name'    => 'lastName',
        'phone_number' => 'phoneNumber',
        'address'      => 'address',
        'city'         => 'city',
        'country'      => 'country',
    ];

    public function __construct(
        private ITransactionManager $txManager,
        private IPlannerService $planner,
        private ICheckoutRepository $checkoutRepo,
        private IUserRepository $users,
        private ITicketDeliveryService $ticketDelivery,
    ) {
    }

    public function confirmCheckout(User $user): array
    {
        $planner = $this->planner->getDetailedPlanner();
        if (!empty($planner['is_empty'])) {
            return ['success' => false, 'message' => 'Your planner is empty.'];
        }

        if ($this->missingCheckoutDetails($user) !== []) {
            return ['success' => false, 'message' => 'Please complete your details.'];
        }

        $items = [];
        foreach ((array) ($planner['items'] ?? []) as $item) {
            if (!empty($item['is_valid'])) {
                $items[] = $item;
            }
        }

        if ($items === []) {
            return ['success' => false, 'message' => 'No valid items in planner.'];
        }

        try {
            $invoiceId = $this->txManager->run(function () use ($items, $user, $planner): int {
                $this->validateItemStock($items);

                $invoiceId = $this->checkoutRepo->createInvoice(
                    $user->getId(),
                    (float) ($planner['total_price_value'] ?? 0)
                );

                $this->persistOrderItems($invoiceId, $user->getId(), $items);

                return $invoiceId;
            });
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('CheckoutService::confirmCheckout failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Could not complete checkout.'];
        }

        $this->deliverOrderConfirmation($user, $invoiceId);
        $this->planner->clear();

        return ['success' => true, 'order_id' => $invoiceId];
    }

    public function getPlannerSummary(): PlannerSummary
    {
        return $this->planner->getPlannerSummary();
    }

    public function loadCheckoutUser(int $userId): ?User
    {
        return $this->users->findById($userId);
    }

    public function missingCheckoutDetails(User $user): array
    {
        $missing = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            $method = $this->fieldToGetter($field);
            if (trim((string) $user->{$method}()) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function saveCheckoutDetails(int $userId, array $details): void
    {
        $this->users->updateCheckoutDetails($userId, $details);
    }

    public function setFlash(\App\Models\FlashType $type, string $message): void
    {
        $this->planner->setFlash($type, $message);
    }

    public function consumeFlash(): ?array
    {
        return $this->planner->consumeFlash();
    }

    private function validateItemStock(array $items): void
    {
        foreach ($items as $item) {
            $event = $this->checkoutRepo->findEventForUpdate((int) $item['event_id']);
            if ($event === null || (int) $event['available_tickets'] < (int) $item['quantity']) {
                throw new \RuntimeException(
                    'Sorry, "' . (string) ($event['name'] ?? 'Event') . '" is out of stock.'
                );
            }
        }
    }

    private function persistOrderItems(int $invoiceId, int $userId, array $items): void
    {
        foreach ($items as $item) {
            $eventId  = (int) $item['event_id'];
            $quantity = (int) $item['quantity'];
            $price    = (float) ($item['unit_price_value'] ?? $item['unit_price'] ?? 0);

            for ($i = 0; $i < $quantity; $i++) {
                $this->checkoutRepo->createTicket($invoiceId, $userId, $eventId, $price);
            }

            $this->checkoutRepo->decrementStock($eventId, $quantity);
        }
    }

    private function deliverOrderConfirmation(User $user, int $invoiceId): void
    {
        try {
            $invoiceRow = $this->checkoutRepo->findInvoiceById($invoiceId);
            if ($invoiceRow === null) {
                error_log('CheckoutService::deliverOrderConfirmation missing invoice ' . $invoiceId);
                return;
            }

            $ticketRows = $this->checkoutRepo->findTicketsByInvoiceId($invoiceId);
            $tickets = array_map(
                static fn(array $ticket): Ticket => Ticket::hydrate($ticket),
                $ticketRows
            );

            $this->ticketDelivery->sendOrderConfirmation(
                $user,
                $invoiceId,
                $tickets,
                (float) ($invoiceRow['total_price'] ?? 0)
            );
        } catch (Throwable $e) {
            error_log('CheckoutService::deliverOrderConfirmation failed: ' . $e->getMessage());
        }
    }

    private function fieldToGetter(string $field): string
    {
        return self::FIELD_GETTERS[$field] ?? $field;
    }
}
