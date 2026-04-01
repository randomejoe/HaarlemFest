<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Repositories\EventRepository;
use Throwable;

final class TicketDeliveryOrchestrator
{
    public function __construct(
        private TicketDeliveryService $ticketDelivery,
        private EventRepository $events,
        private DateTimeFormatter $dateTimeFormatter,
    ) {
    }

    public function buildDeliverableTickets(array $createdTickets): array
    {
        if ($createdTickets === []) {
            return [];
        }

        $eventIds = array_values(array_unique(array_filter(
            array_map('intval', array_column($createdTickets, 'event_id')),
            static fn(int $id): bool => $id > 0
        )));
        $eventsById = $this->events->findByIds($eventIds);

        $deliverable = [];
        foreach ($createdTickets as $ticket) {
            $eventId = (int) ($ticket['event_id'] ?? 0);
            $deliverable[] = $this->buildDeliverableTicket($ticket, $eventsById[$eventId] ?? null);
        }

        return $deliverable;
    }

    public function buildInvoiceForDelivery(
        int $invoiceId,
        array $attempt,
        array $tickets
    ): array {
        $linesByKey = [];

        foreach ($tickets as $ticket) {
            $eventKey = (string) ($ticket['event_id'] ?? 'unknown');
            $priceKey = number_format((float) ($ticket['ticket_price_value'] ?? 0), 2, '.', '');
            $key = $eventKey . '|' . $priceKey;

            if (!isset($linesByKey[$key])) {
                $linesByKey[$key] = [
                    'event_name' => (string) ($ticket['event_name'] ?? 'Event'),
                    'event_date' => (string) ($ticket['event_date'] ?? '-'),
                    'event_time' => (string) ($ticket['event_time'] ?? '-'),
                    'venue' => (string) ($ticket['venue'] ?? 'Venue to be announced'),
                    'quantity' => 0,
                    'unit_price_value' => (float) ($ticket['ticket_price_value'] ?? 0),
                    'line_total_value' => 0.0,
                ];
            }

            $linesByKey[$key]['quantity']++;
            $linesByKey[$key]['line_total_value'] += (float) ($ticket['ticket_price_value'] ?? 0);
        }

        return [
            'invoice_id' => $invoiceId,
            'invoice_number' => 'INV-' . $invoiceId,
            'issued_at' => $this->dateTimeFormatter->currentDateTime(),
            'currency' => (string) ($attempt['currency'] ?? 'EUR'),
            'total_price_value' => (float) ($attempt['total_price'] ?? 0),
            'total_tickets' => count($tickets),
            'items' => array_values($linesByKey),
        ];
    }

    /**
     * @return array{email_warning: ?string, message: string}
     */
    public function deliverPurchaseEmails(
        User $user,
        array $attempt,
        array $createdTickets
    ): array {
        $ticketsForDelivery = $this->buildDeliverableTickets($createdTickets);
        $invoiceId = (int) ($attempt['invoice_id'] ?? 0);
        $invoiceForDelivery = $this->buildInvoiceForDelivery($invoiceId, $attempt, $ticketsForDelivery);

        $warnings = [];

        try {
            $this->ticketDelivery->sendPurchasedTicketsEmail($user, $attempt, $ticketsForDelivery);
        } catch (Throwable $emailError) {
            error_log('Ticket email delivery failed: ' . $emailError->getMessage());
            $warnings[] = 'ticket email delivery failed';
        }

        try {
            $this->ticketDelivery->sendInvoiceEmail($user, $attempt, $invoiceForDelivery);
        } catch (Throwable $emailError) {
            error_log('Invoice email delivery failed: ' . $emailError->getMessage());
            $warnings[] = 'invoice email delivery failed';
        }

        $warningMessage = null;
        if ($warnings !== []) {
            $warningMessage = 'Payment confirmed, but ' . implode(' and ', $warnings) . '.';
        }

        return [
            'email_warning' => $warningMessage,
            'message' => $warningMessage ?? 'Payment confirmed. Ticket and invoice PDFs were sent by email.',
        ];
    }

    private function buildDeliverableTicket(array $ticket, ?Event $event): array
    {
        $eventId = (int) ($ticket['event_id'] ?? 0);
        $ticketPriceValue = (float) ($ticket['ticket_price'] ?? 0);

        if ($event === null) {
            return [
                'ticket_id' => (int) ($ticket['ticket_id'] ?? 0),
                'event_id' => $eventId > 0 ? $eventId : null,
                'verification_code' => (string) ($ticket['verification_code'] ?? ''),
                'event_name' => 'Event unavailable',
                'event_date' => '-',
                'event_time' => '-',
                'venue' => 'Unknown venue',
                'ticket_price_value' => $ticketPriceValue,
                'ticket_price' => number_format($ticketPriceValue, 2),
            ];
        }

        return [
            'ticket_id' => (int) ($ticket['ticket_id'] ?? 0),
            'event_id' => $eventId,
            'verification_code' => (string) ($ticket['verification_code'] ?? ''),
            'event_name' => $event->getName(),
            'event_date' => $event->startsAt()->format('D j M Y'),
            'event_time' => $event->formattedTimeRange(),
            'venue' => $event->venue(),
            'ticket_price_value' => $ticketPriceValue,
            'ticket_price' => number_format($ticketPriceValue, 2),
        ];
    }
}
