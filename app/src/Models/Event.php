<?php

namespace App\Models;
use App\Models\CmsItem;
use App\Models\HistoryTourLanguage;

class Event extends CmsItem
{
    public function __construct(
        private int $id,
        private string $name,
        private string $location,
        private \DateTimeImmutable $start_time,
        private \DateTimeImmutable $end_time,
        private int $ticket_price,
        private int $ticket_amount,
        private ?int $sold_tickets,
        private ?HistoryTourLanguage $language,
        private ?string $description,
        private string $category,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['event_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            location: (string) ($data['location'] ?? ''),
            start_time: (new \DateTimeImmutable($data['start_time'])),
            end_time: (new \DateTimeImmutable($data['end_time'])),
            ticket_price: (int) ($data['ticket_price']),
            ticket_amount: (int) ($data['ticket_amount']),
            sold_tickets: isset($data['sold_tickets']) ? $data['sold_tickets'] : null,
            language: isset($data['language']) ? HistoryTourLanguage::convertToLanguage($data['language']) : null,
            description: isset($data['description']) ? $data['description'] : null,
            category: (string) ($data['category'] ?? ''),
        );
    }

    public function getId(): int {
        return $this->id;
    }
    public function getName(): string {
        return $this->name;
    }
    public function getLocation(): string
    {
        return $this->location;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->start_time;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->end_time;
    }

    public function getTicketPrice(): int
    {
        return $this->ticket_price;
    }

    public function getTicketAmount(): int
    {
        return $this->ticket_amount;
    }

    public function getSoldTickets(): ?int
    {
        return $this->sold_tickets;
    }

    public function getLanguage(): ?HistoryTourLanguage
    {
        return $this->language;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
