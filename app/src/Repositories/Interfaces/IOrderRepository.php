<?php

namespace App\Repositories\Interfaces;

interface IOrderRepository
{
    public function findByUserId(int $userId): array;

    public function getAllOrders(): array;
}
