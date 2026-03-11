<?php

namespace App\Models;

use DateTimeImmutable;

class Event
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $location,
        private string $startTime,
        private string $endTime,
        private float $ticketPrice,
        private ?int $ticketAmount,
        private string $description,
        private ?string $category,
        private string $venue,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
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

    public function description(): string
    {
        return $this->description;
    }

    public function category(): ?string
    {
        return $this->category;
    }

    public function venue(): string
    {
        return $this->venue;
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
