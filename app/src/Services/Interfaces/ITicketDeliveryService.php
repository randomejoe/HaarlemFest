<?php

namespace App\Services\Interfaces;

use App\Models\Ticket;
use App\Models\User;

interface ITicketDeliveryService
{
    /**
     * @param Ticket[] $tickets
     */
    public function sendOrderConfirmation(User $user, int $orderId, array $tickets, float $total): void;
}
