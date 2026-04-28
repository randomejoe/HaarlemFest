<?php

namespace App\Models;

final class Ticket
{
    public function __construct(private array $data)
    {
    }

    public static function hydrate(array $row): self
    {
        return new self($row);
    }

    public function ticketId(): int
    {
        return (int) ($this->data['ticket_id'] ?? 0);
    }

    public function eventId(): int
    {
        return (int) ($this->data['event_id'] ?? 0);
    }

    public function eventName(): string
    {
        return (string) ($this->data['event_name'] ?? 'Event');
    }

    public function eventDate(): string
    {
        return (string) ($this->data['event_date'] ?? '');
    }

    public function eventTime(): string
    {
        return (string) ($this->data['event_time'] ?? '');
    }

    public function venue(): string
    {
        return (string) ($this->data['venue'] ?? 'Venue to be announced');
    }

    public function verificationCode(): string
    {
        return (string) ($this->data['verification_code'] ?? '');
    }

    public function familyTicket(): bool
    {
        return (bool) ($this->data['family_ticket'] ?? false);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
