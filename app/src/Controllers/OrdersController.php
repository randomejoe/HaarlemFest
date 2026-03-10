<?php

namespace App\Controllers;

use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\View;

class OrdersController
{
    private AuthService $auth;
    private UserRepository $users;
    private OrderRepository $orders;

    public function __construct(AuthService $auth, UserRepository $users, OrderRepository $orders)
    {
        $this->auth = $auth;
        $this->users = $users;
        $this->orders = $orders;
    }

    public function show(): void
    {
        $sessionUser = $this->auth->currentUser();
        if ($sessionUser === null) {
            header('Location: /login?redirect=' . urlencode('/orders'));
            exit;
        }

        $user = $this->users->findById((int) $sessionUser['user_id']);
        if ($user === null) {
            $this->auth->logout();
            header('Location: /login?redirect=' . urlencode('/orders'));
            exit;
        }

        $orders = $this->orders->findByUserId((int) $user['user_id']);

        echo View::render('orders', [
            'orders' => $orders,
        ]);
    }
}
