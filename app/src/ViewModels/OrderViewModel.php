<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\OrderSummary;
use App\Models\PurchasedTicket;
use DateTimeImmutable;

final class OrderViewModel
{
    public function __construct(private OrderSummary $order)
    {
    }

    public function toArray(): array
    {
        return [
            'invoice_id' => $this->order->invoiceId(),
            'created_at' => $this->order->createdAt(),
            'created_at_formatted' => $this->formatDateTime($this->order->createdAt()),
            'total_price_value' => $this->order->totalPriceValue(),
            'total_price' => $this->formatCurrency($this->order->totalPriceValue()),
            'ticket_count' => $this->order->ticketCount(),
            'items' => array_map(
                fn(PurchasedTicket $ticket): array => $this->ticketToArray($ticket),
                $this->order->items()
            ),
        ];
    }

    private function ticketToArray(PurchasedTicket $ticket): array
    {
        return [
            'event_id' => $ticket->eventId(),
            'event_name' => $ticket->eventName(),
            'venue' => $ticket->venue(),
            'schedule' => $this->formatSchedule($ticket->startTime(), $ticket->endTime()),
            'unit_price_value' => $ticket->unitPriceValue(),
            'unit_price' => $this->formatCurrency($ticket->unitPriceValue()),
            'quantity' => $ticket->quantity(),
            'line_total_value' => $ticket->lineTotalValue(),
            'line_total' => $this->formatCurrency($ticket->lineTotalValue()),
            'ticket_numbers' => $ticket->ticketNumbers(),
            'verification_codes' => $ticket->verificationCodes(),
            'family_ticket' => $ticket->familyTicket(),
        ];
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2);
    }

    private function formatDateTime(string $datetime): string
    {
        if ($datetime === '') {
            return 'Unknown date';
        }

        try {
            return (new DateTimeImmutable($datetime))->format('D j M Y, H:i');
        } catch (\Throwable) {
            return 'Unknown date';
        }
    }

    private function formatSchedule(string $startTime, string $endTime): string
    {
        if ($startTime === '') {
            return 'Schedule unavailable';
        }

        try {
            $start = new DateTimeImmutable($startTime);
            $label = $start->format('D j M Y H:i');

            if ($endTime === '') {
                return $label;
            }

            $end = new DateTimeImmutable($endTime);
            return $label . ' - ' . $end->format('H:i');
        } catch (\Throwable) {
            return 'Schedule unavailable';
        }
    }
}
