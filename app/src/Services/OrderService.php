<?php

namespace App\Services;

use App\Repositories\Interfaces\IOrderRepository;
use App\Services\Interfaces\IOrderService;

class OrderService implements CMSServiceInterface, IOrderService
{
    private IOrderRepository $repository;

    public function __construct(IOrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getOrdersForUser(int $userId): array
    {
        return $this->repository->findByUserId($userId);
    }

    public function getForEdit(int $id)
    {
        return null;
    }

    public function isNameEditable(): bool
    {
        return false;
    }

    // You can't edit orders
    public function update(int $id, array $postData)
    {
        throw new \BadMethodCallException('Unable to edit orders through CMS');
    }

    // Can't delete orders either
    public function delete(int $id)
    {
        throw new \BadMethodCallException('Unable to delete orders through CMS');
    }

    public function getAll()
    {
        return $this->repository->getAllOrders();
    }
}
