<?php

namespace App\Models;

use DateTimeImmutable;
use JsonSerializable;
use App\Models\CmsItem;
use App\Models\HistoryTourLanguage;

class Event extends CmsItem implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $location,
        private string $startTime,
        private string $endTime,
        private float $ticketPrice,
        private ?int $ticketAmount,
        private ?string $description,
        private ?string $category,
        private ?string $venue,
        private ?int $soldTickets,
        private ?HistoryTourLanguage $language,
        private ?string $artistImg = null,
    ) {}

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->ticketPrice,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['event_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            location: isset($data['location']) ? $data['location'] : null,
            startTime: (string) ($data['start_time']),
            endTime: (string) ($data['end_time']),
            ticketPrice: (int) ($data['ticket_price']),
            ticketAmount: isset($data['ticket_amount']) ? $data['ticket_amount'] : null,
            soldTickets: isset($data['sold_tickets']) ? (int) $data['sold_tickets'] : null,
            language: isset($data['language']) ? HistoryTourLanguage::convertToLanguage($data['language']) : null,
            description: isset($data['description']) ? $data['description'] : null,
            category: isset($data['category']) ? $data['category'] : null,
            venue: isset($data['venue']) ? $data['venue'] : null,
            artistImg: isset($data['artist_img']) ? $data['artist_img'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    public function startTime(): string
    {
        return $this->startTime;
    }

    public function endTime(): string
    {
        return $this->endTime;
    }

    public function startsAt(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->startTime);
    }

    public function endsAt(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->endTime);
    }

    public function ticketPrice(): float
    {
        return $this->ticketPrice;
    }

    public function ticketAmount(): ?int
    {
        return $this->ticketAmount;
    }

    public function getSoldTickets(): ?int
    {
        return $this->soldTickets;
    }

    public function getLanguage(): ?HistoryTourLanguage
    {
        return $this->language;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function category(): ?string
    {
        return $this->category;
    }

    public function venue(): ?string
    {
        return $this->venue;
    }

    public function artistImg(): ?string
    {
        return $this->artistImg;
    }

    public function hasTrackedStock(): bool
    {
        return $this->ticketAmount !== null;
    }

    public function seatCount(): ?int
    {
        if ($this->ticketAmount === null) {
            return null;
        }

        return max(0, $this->ticketAmount);
    }

    public function isFree(): bool
    {
        return $this->ticketPrice <= 0.0;
    }

    public function isSoldOut(): bool
    {
        return $this->hasTrackedStock() && $this->seatCount() <= 0;
    }

    public function canBePlanned(): bool
    {
        return !$this->isFree() && (!$this->hasTrackedStock() || !$this->isSoldOut());
    }

    public function formattedTimeRange(string $format = 'H:i'): string
    {
        return $this->startsAt()->format($format) . ' - ' . $this->endsAt()->format($format);
    }

    public function formattedPlannerTime(): string
    {
        return $this->startsAt()->format('D j M H:i') . ' - ' . $this->endsAt()->format('H:i');
    }
}
