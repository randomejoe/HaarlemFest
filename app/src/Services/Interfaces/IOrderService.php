<?php

namespace App\Services\Interfaces;

interface IOrderService
{
    public function getOrdersForUser(int $userId): array;
}
