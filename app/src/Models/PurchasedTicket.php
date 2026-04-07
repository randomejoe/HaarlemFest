<?php

namespace App\Models;

/**
 * One grouped line item inside an order (all tickets for the same event,
 * at the same price, within a single invoice).
 *
 * Build via the static factory:
 *   PurchasedTicket::fromAggregated($itemMapEntry)
 *
 * The array shape it accepts is the one that OrderRepository accumulates
 * while iterating over DB rows (items_map entries).
 * Call toArray() to get the flat shape the orders view expects.
 */
class PurchasedTicket
{
    private function __construct(
        private ?int   $eventId,
        private string $eventName,
        private string $venue,
        private string $schedule,
        private float  $unitPriceValue,
        private int    $quantity,
        private float  $lineTotalValue,
        private array  $ticketNumbers,
        private array  $verificationCodes,
        private bool   $familyTicket,
    ) {
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Build from one completed items_map entry as assembled by OrderRepository.
     */
    public static function fromAggregated(array $data): self
    {
        $quantity = ($data['quantity']          ?? 0);
        $totalValue = ($data['line_total_value']  ?? 0.0);
        if ($data['family_ticket']) {
            $totalValue = 60 * ceil($quantity/4);
        }
        return new self(
            eventId:           isset($data['event_id']) && $data['event_id'] !== null
                                   ? (int) $data['event_id']
                                   : null,
            eventName:         (string) ($data['event_name']  ?? 'Event unavailable'),
            venue:             (string) ($data['venue']        ?? 'Venue to be announced'),
            schedule:          (string) ($data['schedule']     ?? 'Schedule unavailable'),
            unitPriceValue:    (float)  ($data['unit_price_value']  ?? 0.0),
            quantity:          (int)    $quantity,
            lineTotalValue:    (float)  $totalValue,
            ticketNumbers:     (array)  ($data['ticket_numbers']    ?? []),
            verificationCodes: (array)  ($data['verification_codes'] ?? []),
            familyTicket:      ($data['family_ticket'] == 1 ? true : false),
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function eventId(): ?int    { return $this->eventId; }
    public function eventName(): string { return $this->eventName; }
    public function venue(): string    { return $this->venue; }
    public function schedule(): string { return $this->schedule; }
    public function unitPriceValue(): float { return $this->unitPriceValue; }
    public function quantity(): int    { return $this->quantity; }
    public function lineTotalValue(): float { return $this->lineTotalValue; }

    /** @return string[] */
    public function ticketNumbers(): array { return $this->ticketNumbers; }

    /** @return string[] */
    public function verificationCodes(): array { return $this->verificationCodes; }

    // -------------------------------------------------------------------------
    // Array export (keeps view contracts unchanged)
    // -------------------------------------------------------------------------

    /** Shape expected by the orders view. */
    public function toArray(): array
    {
        return [
            'event_id'           => $this->eventId,
            'event_name'         => $this->eventName,
            'venue'              => $this->venue,
            'schedule'           => $this->schedule,
            'unit_price_value'   => $this->unitPriceValue,
            'unit_price'         => number_format($this->unitPriceValue, 2),
            'quantity'           => $this->quantity,
            'line_total_value'   => $this->lineTotalValue,
            'line_total'         => number_format($this->lineTotalValue, 2),
            'ticket_numbers'     => $this->ticketNumbers,
            'verification_codes' => $this->verificationCodes,
            'family_ticket'      => $this->familyTicket,
        ];
    }
}
