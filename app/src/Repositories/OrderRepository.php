<?php

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

class OrderRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                i.invoice_id,
                i.total_price,
                i.created_at AS invoice_created_at,
                t.ticket_id,
                t.ticket_price,
                t.verification_code,
                e.event_id,
                e.name AS event_name,
                e.start_time,
                e.end_time,
                COALESCE(NULLIF(e.location, \'\'), v.location) AS venue_location
            FROM invoices i
            LEFT JOIN tickets t ON t.invoice_id = i.invoice_id
            LEFT JOIN events e ON e.event_id = t.event_id
            LEFT JOIN venues v ON v.venue_id = e.venue_id
            WHERE i.user_id = :user_id
            ORDER BY i.created_at DESC, i.invoice_id DESC, t.ticket_id ASC'
        );

        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll() ?: [];

        $orders = [];

        foreach ($rows as $row) {
            $invoiceId = (int) ($row['invoice_id'] ?? 0);
            if ($invoiceId <= 0) {
                continue;
            }

            if (!isset($orders[$invoiceId])) {
                $orders[$invoiceId] = [
                    'invoice_id' => $invoiceId,
                    'created_at' => (string) ($row['invoice_created_at'] ?? ''),
                    'created_at_formatted' => $this->formatDateTime((string) ($row['invoice_created_at'] ?? '')),
                    'total_price_value' => (float) ($row['total_price'] ?? 0),
                    'total_price' => number_format((float) ($row['total_price'] ?? 0), 2),
                    'ticket_count' => 0,
                    'items_map' => [],
                ];
            }

            $ticketId = (int) ($row['ticket_id'] ?? 0);
            if ($ticketId <= 0) {
                continue;
            }

            $orders[$invoiceId]['ticket_count']++;

            $eventId = (int) ($row['event_id'] ?? 0);
            $itemKey = $eventId > 0 ? ('event_' . $eventId) : ('ticket_' . $ticketId);

            if (!isset($orders[$invoiceId]['items_map'][$itemKey])) {
                $start = (string) ($row['start_time'] ?? '');
                $end = (string) ($row['end_time'] ?? '');

                $orders[$invoiceId]['items_map'][$itemKey] = [
                    'event_id' => $eventId > 0 ? $eventId : null,
                    'event_name' => (string) ($row['event_name'] ?? 'Event unavailable'),
                    'venue' => (string) ($row['venue_location'] ?? 'Venue to be announced'),
                    'schedule' => $this->formatSchedule($start, $end),
                    'unit_price_value' => (float) ($row['ticket_price'] ?? 0),
                    'unit_price' => number_format((float) ($row['ticket_price'] ?? 0), 2),
                    'quantity' => 0,
                    'line_total_value' => 0.0,
                    'line_total' => number_format(0, 2),
                    'ticket_numbers' => [],
                    'verification_codes' => [],
                ];
            }

            $orders[$invoiceId]['items_map'][$itemKey]['quantity']++;
            $orders[$invoiceId]['items_map'][$itemKey]['line_total_value'] += (float) ($row['ticket_price'] ?? 0);
            $orders[$invoiceId]['items_map'][$itemKey]['line_total'] = number_format(
                (float) $orders[$invoiceId]['items_map'][$itemKey]['line_total_value'],
                2
            );

            $orders[$invoiceId]['items_map'][$itemKey]['ticket_numbers'][] = 'T-' . $ticketId;

            $code = trim((string) ($row['verification_code'] ?? ''));
            if ($code !== '') {
                $orders[$invoiceId]['items_map'][$itemKey]['verification_codes'][] = $code;
            }
        }

        $result = [];
        foreach ($orders as $order) {
            $order['items'] = array_values($order['items_map']);
            unset($order['items_map']);
            $result[] = $order;
        }

        return $result;
    }

    private function formatDateTime(string $datetime): string
    {
        if ($datetime === '') {
            return 'Unknown date';
        }

        try {
            $value = new DateTimeImmutable($datetime);
            return $value->format('D j M Y, H:i');
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
