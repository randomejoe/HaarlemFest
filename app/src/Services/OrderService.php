<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\OrderRepository;

class OrderService implements CMSServiceInterface
{
    private OrderRepository $repository;
    
    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getForEdit(int $id)
    {
        header('Location: /cms/orders');
        return null;
    }

    public function isNameEditable(): bool
    {
        return false;
    }

    // You can't edit orders
    public function update(int $id, array $postData): bool
    {
        return false;
    }

    // Can't delete orders either
    public function delete(int $id): bool
    {
        return false;
    }

    public function getAll()
    {
        return $this->repository->getAllOrders();
    }
}
