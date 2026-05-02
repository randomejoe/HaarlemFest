<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\FlashType;
use App\Services\Interfaces\ICheckoutService;
use App\Services\Interfaces\IAuthService;
use App\View;

class CheckoutController
{
    private const REQUIRED_FIELDS = ['first_name', 'last_name', 'address', 'city', 'country', 'phone_number'];
    private const CHECKOUT_PATH = '/checkout';

    public function __construct(
        private ICheckoutService $checkout,
        private IAuthService $auth,
    ) {
    }

    public function show(): void
    {
        $user = $this->requireCheckoutUser();
        echo View::render('checkout', $this->checkout->buildCheckoutView($user));
    }

    public function saveDetails(): void
    {
        $user = $this->requireCheckoutUser();
        $details = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $_POST[$field] ?? '';
            $details[$field] = is_scalar($value) ? trim((string) $value) : '';
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($details[$field])) {
                $this->checkout->setFlash(FlashType::Error, 'Please complete all required checkout details.');
                $this->redirect(self::CHECKOUT_PATH);
            }
        }

        $this->checkout->saveCheckoutDetails($user->getId(), $details);
        $this->checkout->setFlash(FlashType::Success, 'Your checkout details were saved.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    public function confirm(): void
    {
        $user = $this->requireCheckoutUser();
        $result = $this->checkout->confirmCheckout($user);

        if (!empty($result['success'])) {
            $this->checkout->setFlash(FlashType::Success, 'Thank you! Your order has been placed.');
            $this->redirect('/orders');
        }

        $this->checkout->setFlash(FlashType::Error, (string) ($result['message'] ?? 'Checkout could not be completed.'));
        $this->redirect(self::CHECKOUT_PATH);
    }

    private function requireCheckoutUser(): User
    {
        $sessionUser = $this->auth->currentUser();
        if (!$sessionUser) {
            $this->redirect('/login?redirect=' . urlencode(self::CHECKOUT_PATH));
        }

        $user = $this->checkout->loadCheckoutUser($sessionUser->getId());
        if (!$user) {
            $this->auth->logout();
            $this->redirect('/login?redirect=' . urlencode(self::CHECKOUT_PATH));
        }

        return $user;
    }

    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
