<?php

namespace App\Controllers;

use App\Services\Interfaces\IAuthService;
use App\Services\Interfaces\IOrderService;
use App\View;
use App\ViewModels\OrderViewModel;

class OrdersController
{
    private IAuthService $auth;
    private IOrderService $orders;

    public function __construct(IAuthService $auth, IOrderService $orders)
    {
        $this->auth = $auth;
        $this->orders = $orders;
    }

    public function show(): void
    {
        $sessionUser = $this->auth->currentUser();
        if ($sessionUser === null) {
            header('Location: /login?redirect=' . urlencode('/orders'));
            exit;
        }

        $orders = array_map(
            static fn($order): array => (new OrderViewModel($order))->toArray(),
            $this->orders->getOrdersForUser($sessionUser->getId())
        );

        echo View::render('orders', [
            'orders' => $orders,
        ]);
    }
}
