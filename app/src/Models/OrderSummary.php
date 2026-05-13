<?php

namespace App\Models;

/**
 * One paid invoice and all of its grouped ticket line items.
 *
 * Build via the static factory:
 *   OrderSummary::fromOrderData($orderData, $purchasedTickets)
 *
 * The $orderData array is the per-invoice accumulator built by OrderRepository.
 * Call toArray() to get the flat shape the orders view and controller expect.
 */
class OrderSummary
{
    /**
     * @param PurchasedTicket[] $items
     */
    private function __construct(
        private int    $invoiceId,
        private string $createdAt,
        private float  $totalPriceValue,
        private int    $ticketCount,
        private array  $items,
    ) {
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * @param array             $data   Per-invoice accumulator from OrderRepository.
     * @param PurchasedTicket[] $items  Already-built line items for this invoice.
     */
    public static function fromOrderData(array $data, array $items): self
    {
        return new self(
            invoiceId:          (int)    ($data['invoice_id']         ?? 0),
            createdAt:          (string) ($data['created_at']         ?? ''),
            totalPriceValue:    (float)  ($data['total_price_value']  ?? 0.0),
            ticketCount:        (int)    ($data['ticket_count']       ?? 0),
            items:              $items,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function invoiceId(): int    { return $this->invoiceId; }
    public function createdAt(): string { return $this->createdAt; }
    public function totalPriceValue(): float { return $this->totalPriceValue; }
    public function ticketCount(): int { return $this->ticketCount; }

    /** @return PurchasedTicket[] */
    public function items(): array { return $this->items; }

    public function isEmpty(): bool { return $this->items === []; }

    // -------------------------------------------------------------------------
    // Array export (keeps controller/view contracts unchanged)
    // -------------------------------------------------------------------------

    /** Shape expected by OrdersController and the orders view. */
    public function toArray(): array
    {
        return [
            'invoice_id'           => $this->invoiceId,
            'created_at'           => $this->createdAt,
            'total_price_value'    => $this->totalPriceValue,
            'ticket_count'         => $this->ticketCount,
            'items'                => array_map(
                static fn(PurchasedTicket $t): array => $t->toArray(),
                $this->items
            ),
        ];
    }

    public function getName() {
        return '';
    }
    public function getId() {
        return $this->invoiceId;
    }
    public function getTicketCount() {
        return $this->ticketCount;
    }
    public function getTotalPrice() {
        return $this->totalPriceValue;
    }
    public function getEventNames() {
        $events = $this->items;
        $names = [];
        foreach ($events as $event) {
            $names[] = $event->eventName();
        }
        return array_unique($names);
    }
}
