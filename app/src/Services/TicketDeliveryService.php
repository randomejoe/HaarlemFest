<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckoutAttempt;
use App\Models\DeliveryResult;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Interfaces\ITicketDeliveryService;
use RuntimeException;

final class TicketDeliveryService implements ITicketDeliveryService
{
    public function __construct(
        private Mailer $mailer,
        private TicketPdfService $ticketPdfService,
        private InvoicePdfService $invoicePdfService,
        private DateTimeFormatter $dateTimeFormatter,
    ) {
    }

    /**
     * @param Ticket[] $tickets
     */
    public function deliverPurchaseEmails(
        User $user,
        CheckoutAttempt $attempt,
        array $tickets,
        Invoice $invoice
    ): DeliveryResult {
        $warning = null;

        try {
            $this->sendPurchasedTicketsEmail($user, $attempt, $tickets);
        } catch (\Throwable $e) {
            $warning = $e->getMessage();
        }

        try {
            $this->sendInvoiceEmail($user, $attempt, $invoice, $tickets);
        } catch (\Throwable $e) {
            $warning = $warning ?? $e->getMessage();
        }

        return new DeliveryResult(
            true,
            $warning === null ? 'Purchase emails sent.' : 'Purchase emails sent with warnings.',
            $warning
        );
    }

    /**
     * @param Ticket[]|array[] $tickets
     */
    public function sendPurchasedTicketsEmail(User $user, CheckoutAttempt|array $attempt, array $tickets): void
    {
        $toEmail = $user->email();
        if ($toEmail === '') {
            throw new RuntimeException('User email is missing.');
        }

        $attemptData = $attempt instanceof CheckoutAttempt ? $attempt->toArray() : $attempt;
        $toName = $this->resolveCustomerName($user);
        $attemptId = (int) ($attemptData['checkout_attempt_id'] ?? 0);
        $ticketPayload = array_map(
            static fn (Ticket|array $ticket): array => $ticket instanceof Ticket ? $ticket->toArray() : $ticket,
            $tickets
        );

        $ticketPdfPayload = [
            'customer_name' => $toName,
            'purchase_reference' => '#' . $attemptId,
            'issued_at' => $this->dateTimeFormatter->currentDateTime(),
            'tickets' => $ticketPayload,
            'terms' => 'Tickets are non-refundable unless required by law. Please bring a valid ID and this ticket at entry. Each ticket has a unique verification code that will be validated at the venue.',
        ];

        $ticketPdfBinary = $this->ticketPdfService->generate($ticketPdfPayload);

        $subject = 'Haarlem Festival tickets confirmed - Order #' . $attemptId;
        $body = $this->buildTicketEmailBody($toName, $attemptId, $ticketPayload);

        $ticketPdfFilename = 'haarlem-festival-tickets-order-' . $attemptId . '.pdf';

        $this->mailer->send(
            $toEmail,
            $toName,
            $subject,
            $body,
            [
                [
                    'filename' => $ticketPdfFilename,
                    'content' => $ticketPdfBinary,
                    'mime' => 'application/pdf',
                ],
            ]
        );
    }

    /**
     * @param Ticket[]|array[] $tickets
     */
    public function sendInvoiceEmail(User $user, CheckoutAttempt|array $attempt, Invoice|array $invoice, array $tickets = []): void
    {
        $toEmail = $user->email();
        if ($toEmail === '') {
            throw new RuntimeException('User email is missing.');
        }

        $attemptData = $attempt instanceof CheckoutAttempt ? $attempt->toArray() : $attempt;
        $invoiceData = $invoice instanceof Invoice ? $invoice->toArray() : $invoice;
        $toName = $this->resolveCustomerName($user);
        $attemptId = (int) ($attemptData['checkout_attempt_id'] ?? 0);
        $ticketPayload = array_map(
            static fn (Ticket|array $ticket): array => $ticket instanceof Ticket ? $ticket->toArray() : $ticket,
            $tickets
        );
        $invoiceItems = $this->normalizeInvoiceItems((array) ($invoiceData['items'] ?? []), $ticketPayload);

        $invoicePdfPayload = [
            'invoice_number' => (string) ($invoiceData['invoice_number'] ?? ('INV-' . ((int) ($invoiceData['invoice_id'] ?? 0)))),
            'order_reference' => '#' . $attemptId,
            'issued_at' => (string) ($invoiceData['issued_at'] ?? $this->dateTimeFormatter->currentDateTime()),
            'customer_name' => $toName,
            'customer_email' => $toEmail,
            'customer_address' => trim((string) ($user->address() ?? '')),
            'customer_phone' => trim((string) ($user->phoneNumber() ?? '')),
            'currency' => (string) ($invoiceData['currency'] ?? 'EUR'),
            'total_price' => (float) ($invoiceData['total_price_value'] ?? ($invoiceData['total_price'] ?? 0)),
            'total_tickets' => (int) ($invoiceData['total_tickets'] ?? count($ticketPayload)),
            'items' => $invoiceItems,
            'terms' => 'This invoice confirms your completed ticket purchase. Keep this document for your records.',
        ];

        $invoicePdfBinary = $this->invoicePdfService->generate($invoicePdfPayload);

        $invoiceId = (int) ($invoiceData['invoice_id'] ?? 0);
        $subject = 'Haarlem Festival invoice - Order #' . $attemptId;
        $body = $this->buildInvoiceEmailBody($user, $toName, $attemptId, $invoiceData, $invoiceItems);

        $invoicePdfFilename = 'haarlem-festival-invoice-' . $invoiceId . '.pdf';

        $this->mailer->send(
            $toEmail,
            $toName,
            $subject,
            $body,
            [
                [
                    'filename' => $invoicePdfFilename,
                    'content' => $invoicePdfBinary,
                    'mime' => 'application/pdf',
                ],
            ]
        );
    }

    private function resolveCustomerName(User $user): string
    {
        $firstName = trim((string) ($user->firstName() ?? ''));
        $lastName = trim((string) ($user->lastName() ?? ''));

        $fullName = trim($firstName . ' ' . $lastName);
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim($user->username());
        if ($username !== '') {
            return $username;
        }

        return trim($user->email() !== '' ? $user->email() : 'Customer');
    }

    /**
     * @param array<int, array<string, mixed>> $tickets
     */
    private function buildTicketEmailBody(string $customerName, int $attemptId, array $tickets): string
    {
        $listItems = '';

        foreach ($tickets as $ticket) {
            $eventName = htmlspecialchars((string) ($ticket['event_name'] ?? 'Event'), ENT_QUOTES, 'UTF-8');
            $eventDate = htmlspecialchars((string) ($ticket['event_date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $eventTime = htmlspecialchars((string) ($ticket['event_time'] ?? ''), ENT_QUOTES, 'UTF-8');
            $venue = htmlspecialchars((string) ($ticket['venue'] ?? ''), ENT_QUOTES, 'UTF-8');
            $ticketId = (int) ($ticket['ticket_id'] ?? 0);
            $code = htmlspecialchars((string) ($ticket['verification_code'] ?? ''), ENT_QUOTES, 'UTF-8');

            $listItems .= '<li style="margin-bottom:10px;">'
                . '<strong>' . $eventName . '</strong><br>'
                . 'Date: ' . $eventDate . '<br>'
                . 'Time: ' . $eventTime . '<br>'
                . 'Venue: ' . $venue . '<br>'
                . 'Ticket number: T-' . $ticketId . '<br>'
                . 'Verification code: <strong>' . $code . '</strong>'
                . '</li>';
        }

        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');

        return '<div style="font-family:Arial,sans-serif;color:#1b1b1b;line-height:1.5;">'
            . '<h2 style="color:#002c53;margin:0 0 12px;">Your Haarlem Festival Tickets</h2>'
            . '<p>Hello ' . $safeName . ',</p>'
            . '<p>Your payment has been confirmed for order <strong>#' . $attemptId . '</strong>.</p>'
            . '<p>Your tickets are attached as a PDF. You can print or save them for entry.</p>'
            . '<h3 style="margin:18px 0 8px;color:#003a70;">Ticket Summary</h3>'
            . '<ul style="padding-left:18px;margin:0;">' . $listItems . '</ul>'
            . '<p style="margin-top:18px;">A separate invoice email with PDF attachment is sent as well.</p>'
            . '<p style="color:#555;">Haarlem Festival Team</p>'
            . '</div>';
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function buildInvoiceEmailBody(User $user, string $customerName, int $attemptId, array $invoice, array $invoiceItems): string
    {
        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $invoiceId = (int) ($invoice['invoice_id'] ?? 0);
        $total = number_format((float) ($invoice['total_price_value'] ?? ($invoice['total_price'] ?? 0)), 2);
        $currency = htmlspecialchars((string) ($invoice['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8');
        $totalTickets = (int) ($invoice['total_tickets'] ?? 0);
        $address = htmlspecialchars(trim((string) ($user->address() ?? '')), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars(trim((string) ($user->phoneNumber() ?? '')), ENT_QUOTES, 'UTF-8');
        $lineItems = '';
        foreach ($invoiceItems as $item) {
            $eventName = htmlspecialchars((string) ($item['event_name'] ?? 'Event'), ENT_QUOTES, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 0);
            $lineTotal = number_format((float) ($item['line_total_value'] ?? 0), 2);
            $lineItems .= '<li>' . $eventName . ' x' . $quantity . ' - ' . $currency . ' ' . $lineTotal . '</li>';
        }

        return '<div style="font-family:Arial,sans-serif;color:#1b1b1b;line-height:1.5;">'
            . '<h2 style="color:#002c53;margin:0 0 12px;">Your Haarlem Festival Invoice</h2>'
            . '<p>Hello ' . $safeName . ',</p>'
            . '<p>Attached is your invoice PDF for order <strong>#' . $attemptId . '</strong>.</p>'
            . '<p><strong>Invoice number:</strong> INV-' . $invoiceId . '<br>'
            . '<strong>Total tickets:</strong> ' . $totalTickets . '<br>'
            . '<strong>Total amount:</strong> ' . $currency . ' ' . $total . '</p>'
            . ($address !== '' ? ('<p><strong>Address:</strong> ' . $address . '</p>') : '')
            . ($phone !== '' ? ('<p><strong>Phone:</strong> ' . $phone . '</p>') : '')
            . '<h3 style="margin:16px 0 8px;color:#003a70;">Invoice Lines</h3>'
            . '<ul style="padding-left:18px;margin:0;">' . $lineItems . '</ul>'
            . '<p style="margin-top:18px;color:#555;">Haarlem Festival Team</p>'
            . '</div>';
    }

    /**
     * @param array<int, array<string, mixed>> $invoiceItems
     * @param array<int, array<string, mixed>> $tickets
     * @return array<int, array<string, mixed>>
     */
    private function normalizeInvoiceItems(array $invoiceItems, array $tickets): array
    {
        $hasGroupedItems = false;
        foreach ($invoiceItems as $item) {
            if (isset($item['quantity']) || isset($item['line_total_value'])) {
                $hasGroupedItems = true;
                break;
            }
        }

        if ($hasGroupedItems) {
            return $invoiceItems;
        }

        if ($tickets === []) {
            return $invoiceItems;
        }

        $linesByKey = [];
        foreach ($tickets as $ticket) {
            $eventId = (int) ($ticket['event_id'] ?? 0);
            $ticketPrice = (float) ($ticket['ticket_price_value'] ?? ($ticket['ticket_price'] ?? 0));
            $key = $eventId . '|' . number_format($ticketPrice, 2, '.', '');

            if (!isset($linesByKey[$key])) {
                $linesByKey[$key] = [
                    'event_name' => (string) ($ticket['event_name'] ?? 'Event'),
                    'event_date' => (string) ($ticket['event_date'] ?? '-'),
                    'event_time' => (string) ($ticket['event_time'] ?? '-'),
                    'venue' => (string) ($ticket['venue'] ?? 'Venue to be announced'),
                    'quantity' => 0,
                    'unit_price_value' => $ticketPrice,
                    'line_total_value' => 0.0,
                ];
            }

            $linesByKey[$key]['quantity']++;
            $linesByKey[$key]['line_total_value'] += $ticketPrice;
        }

        return array_values($linesByKey);
    }
}
