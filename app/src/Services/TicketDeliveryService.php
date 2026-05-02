<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Interfaces\ITicketDeliveryService;

final class TicketDeliveryService implements ITicketDeliveryService
{
    public function __construct(
        private Mailer $mailer,
        private TicketPdfService $ticketPdfService,
        private InvoicePdfService $invoicePdfService,
    ) {
    }

    /**
     * @param Ticket[] $tickets
     */
    public function sendOrderConfirmation(User $user, int $orderId, array $tickets, float $total): void
    {
        try {
            $toEmail = $user->email();
            if ($toEmail === '') {
                return;
            }

            $toName = $this->customerName($user);
            $ticketPayload = array_map(
                static fn(Ticket $ticket): array => $ticket->toArray(),
                $tickets
            );
            $invoiceItems = $this->invoiceItems($ticketPayload);

            $ticketPdfBinary = $this->ticketPdfService->generate([
                'customer_name' => $toName,
                'purchase_reference' => '#' . $orderId,
                'issued_at' => date('Y-m-d H:i:s'),
                'tickets' => $ticketPayload,
                'terms' => 'Tickets are non-refundable unless required by law. Please bring a valid ID and this ticket at entry.',
            ]);

            $invoicePdfBinary = $this->invoicePdfService->generate([
                'invoice_number' => 'INV-' . $orderId,
                'order_reference' => '#' . $orderId,
                'issued_at' => date('Y-m-d H:i:s'),
                'customer_name' => $toName,
                'customer_email' => $toEmail,
                'customer_address' => trim((string) ($user->address() ?? '')),
                'customer_phone' => trim((string) ($user->phoneNumber() ?? '')),
                'currency' => 'EUR',
                'total_price' => $total,
                'total_tickets' => count($ticketPayload),
                'items' => $invoiceItems,
                'terms' => 'This invoice confirms your completed ticket purchase. Keep this document for your records.',
            ]);

            $this->mailer->send(
                $toEmail,
                $toName,
                'Haarlem Festival order confirmed - Order #' . $orderId,
                $this->emailBody($toName, $orderId, $ticketPayload, $total),
                [
                    [
                        'filename' => 'haarlem-festival-tickets-order-' . $orderId . '.pdf',
                        'content' => $ticketPdfBinary,
                        'mime' => 'application/pdf',
                    ],
                    [
                        'filename' => 'haarlem-festival-invoice-' . $orderId . '.pdf',
                        'content' => $invoicePdfBinary,
                        'mime' => 'application/pdf',
                    ],
                ]
            );
        } catch (\Throwable $e) {
            error_log('TicketDeliveryService::sendOrderConfirmation failed: ' . $e->getMessage());
        }
    }

    private function customerName(User $user): string
    {
        $name = trim((string) ($user->firstName() ?? '') . ' ' . (string) ($user->lastName() ?? ''));
        if ($name !== '') {
            return $name;
        }

        if (trim($user->username()) !== '') {
            return trim($user->username());
        }

        return $user->email() !== '' ? $user->email() : 'Customer';
    }

    private function emailBody(string $customerName, int $orderId, array $tickets, float $total): string
    {
        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $items = '';

        foreach ($tickets as $ticket) {
            $eventName = htmlspecialchars((string) ($ticket['event_name'] ?? 'Event'), ENT_QUOTES, 'UTF-8');
            $eventDate = htmlspecialchars((string) ($ticket['event_date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $eventTime = htmlspecialchars((string) ($ticket['event_time'] ?? ''), ENT_QUOTES, 'UTF-8');
            $venue = htmlspecialchars((string) ($ticket['venue'] ?? ''), ENT_QUOTES, 'UTF-8');
            $items .= '<li><strong>' . $eventName . '</strong><br>'
                . 'Date: ' . $eventDate . '<br>'
                . 'Time: ' . $eventTime . '<br>'
                . 'Venue: ' . $venue . '</li>';
        }

        return '<div style="font-family:Arial,sans-serif;color:#1b1b1b;line-height:1.5;">'
            . '<h2 style="color:#002c53;margin:0 0 12px;">Your Haarlem Festival Order</h2>'
            . '<p>Hello ' . $safeName . ',</p>'
            . '<p>Your order <strong>#' . $orderId . '</strong> has been confirmed.</p>'
            . '<p>Your ticket PDF and invoice PDF are attached to this email.</p>'
            . '<ul style="padding-left:18px;margin:0;">' . $items . '</ul>'
            . '<p><strong>Total:</strong> EUR ' . number_format($total, 2) . '</p>'
            . '<p style="color:#555;">Haarlem Festival Team</p>'
            . '</div>';
    }

    private function invoiceItems(array $tickets): array
    {
        $linesByKey = [];

        foreach ($tickets as $ticket) {
            $eventId = (int) ($ticket['event_id'] ?? 0);
            $price = (float) ($ticket['ticket_price_value'] ?? ($ticket['ticket_price'] ?? 0));
            $key = $eventId . '|' . number_format($price, 2, '.', '');

            if (!isset($linesByKey[$key])) {
                $linesByKey[$key] = [
                    'event_name' => (string) ($ticket['event_name'] ?? 'Event'),
                    'event_date' => (string) ($ticket['event_date'] ?? '-'),
                    'event_time' => (string) ($ticket['event_time'] ?? '-'),
                    'venue' => (string) ($ticket['venue'] ?? 'Venue to be announced'),
                    'quantity' => 0,
                    'unit_price_value' => $price,
                    'line_total_value' => 0.0,
                ];
            }

            $linesByKey[$key]['quantity']++;
            $linesByKey[$key]['line_total_value'] += $price;
        }

        return array_values($linesByKey);
    }
}
