<?php

namespace App\Services;

class TicketDeliveryService
{
    private Mailer $mailer;
    private TicketPdfService $ticketPdfService;
    private InvoicePdfService $invoicePdfService;

    public function __construct(
        Mailer $mailer,
        TicketPdfService $ticketPdfService,
        InvoicePdfService $invoicePdfService
    )
    {
        $this->mailer = $mailer;
        $this->ticketPdfService = $ticketPdfService;
        $this->invoicePdfService = $invoicePdfService;
    }

    public function sendPurchasedTicketsEmail(array $user, array $attempt, array $tickets): void
    {
        $toEmail = (string) ($user['email'] ?? '');
        if ($toEmail === '') {
            throw new \RuntimeException('User email is missing.');
        }

        $toName = $this->resolveCustomerName($user);
        $attemptId = (int) ($attempt['checkout_attempt_id'] ?? 0);

        $ticketPdfPayload = [
            'customer_name' => $toName,
            'purchase_reference' => '#' . $attemptId,
            'issued_at' => date('Y-m-d H:i:s'),
            'tickets' => $tickets,
            'terms' => 'Tickets are non-refundable unless required by law. Please bring a valid ID and this ticket at entry. Each ticket has a unique verification code that will be validated at the venue.',
        ];

        $ticketPdfBinary = $this->ticketPdfService->generate($ticketPdfPayload);

        $subject = 'Haarlem Festival tickets confirmed - Order #' . $attemptId;
        $body = $this->buildTicketEmailBody($toName, $attemptId, $tickets);

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

    public function sendInvoiceEmail(array $user, array $attempt, array $invoice): void
    {
        $toEmail = (string) ($user['email'] ?? '');
        if ($toEmail === '') {
            throw new \RuntimeException('User email is missing.');
        }

        $toName = $this->resolveCustomerName($user);
        $attemptId = (int) ($attempt['checkout_attempt_id'] ?? 0);

        $invoicePdfPayload = [
            'invoice_number' => (string) ($invoice['invoice_number'] ?? ('INV-' . ((int) ($invoice['invoice_id'] ?? 0)))),
            'order_reference' => '#' . $attemptId,
            'issued_at' => (string) ($invoice['issued_at'] ?? date('Y-m-d H:i:s')),
            'customer_name' => $toName,
            'customer_email' => $toEmail,
            'customer_address' => trim((string) ($user['address'] ?? '')),
            'customer_phone' => trim((string) ($user['phone_number'] ?? '')),
            'currency' => (string) ($invoice['currency'] ?? 'EUR'),
            'total_price' => (float) ($invoice['total_price_value'] ?? 0),
            'total_tickets' => (int) ($invoice['total_tickets'] ?? 0),
            'items' => (array) ($invoice['items'] ?? []),
            'terms' => 'This invoice confirms your completed ticket purchase. Keep this document for your records.',
        ];

        $invoicePdfBinary = $this->invoicePdfService->generate($invoicePdfPayload);

        $invoiceId = (int) ($invoice['invoice_id'] ?? 0);
        $subject = 'Haarlem Festival invoice - Order #' . $attemptId;
        $body = $this->buildInvoiceEmailBody($user, $toName, $attemptId, $invoice);

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

    private function resolveCustomerName(array $user): string
    {
        $firstName = trim((string) ($user['first_name'] ?? ''));
        $lastName = trim((string) ($user['last_name'] ?? ''));

        $fullName = trim($firstName . ' ' . $lastName);
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) ($user['username'] ?? ''));
        if ($username !== '') {
            return $username;
        }

        return trim((string) ($user['email'] ?? 'Customer'));
    }

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

    private function buildInvoiceEmailBody(array $user, string $customerName, int $attemptId, array $invoice): string
    {
        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $invoiceId = (int) ($invoice['invoice_id'] ?? 0);
        $total = number_format((float) ($invoice['total_price_value'] ?? 0), 2);
        $currency = htmlspecialchars((string) ($invoice['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8');
        $totalTickets = (int) ($invoice['total_tickets'] ?? 0);
        $address = htmlspecialchars(trim((string) ($user['address'] ?? '')), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars(trim((string) ($user['phone_number'] ?? '')), ENT_QUOTES, 'UTF-8');

        $lineItems = '';
        foreach ((array) ($invoice['items'] ?? []) as $item) {
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
}
