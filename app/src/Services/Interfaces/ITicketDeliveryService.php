<?php

namespace App\Services\Interfaces;

use App\Models\CheckoutAttempt;
use App\Models\DeliveryResult;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;

interface ITicketDeliveryService
{
    /**
     * @param Ticket[] $tickets
     */
    public function deliverPurchaseEmails(
        User $user,
        CheckoutAttempt $attempt,
        array $tickets,
        Invoice $invoice
    ): DeliveryResult;
}
