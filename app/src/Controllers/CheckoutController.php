<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\Interfaces\ICheckoutService;
use App\View;

class CheckoutController
{
    private const REQUIRED_FIELDS = ['first_name', 'last_name', 'address', 'city', 'country', 'phone_number'];
    private const CHECKOUT_PATH = '/checkout';

    public function __construct(
        private ICheckoutService $checkout,
        private AuthService $auth,
    ) {
    }

    public function show(): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded();
        $lockedId = $this->checkout->getLockedAttemptId();
        if ($lockedId !== null) {
            $this->redirect('/checkout/pending/' . $lockedId);
        }

        $user = $this->requireCheckoutUser();

        echo View::render('checkout', $this->checkout->buildCheckoutView($user));
    }

    public function saveDetails(): void
    {
        $user = $this->requireCheckoutUser();
        $details = array_map('trim', $_POST);

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($details[$field])) {
                $this->checkout->setFlash('error', 'Please complete all required checkout details.');
                $this->redirect(self::CHECKOUT_PATH);
            }
        }

        $this->checkout->saveCheckoutDetails($user->getId(), $details);
        $this->checkout->setFlash('success', 'Your checkout details were saved.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    public function confirm(): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded(true);
        $lockedId = $this->checkout->getLockedAttemptId();
        if ($lockedId !== null) {
            $this->redirect('/checkout/pending/' . $lockedId);
        }

        $user = $this->requireCheckoutUser();
        if ($this->checkout->missingCheckoutDetails($user)) {
            $this->checkout->setFlash('error', 'Please complete your required details before checkout.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $result = $this->checkout->confirmCheckout(
            $user,
            trim($_POST['idempotency_key'] ?? '')
        )->toArray();

        match ($result['status'] ?? 'unknown') {
            'handoff_created', 'already_pending', 'already_paid' => $this->redirectToPendingAttempt($result['attempt_id'] ?? 0),
            'out_of_stock' => $this->redirectOutOfStock($result),
            default => $this->redirectConfirmError($result)
        };
    }

    public function pending(int $id): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded(true);
        $user = $this->requireSessionUser();
        $viewData = $this->checkout->buildPendingView($id, $user);
        $attempt = $viewData['attempt'];

        if (!$attempt || $attempt['user_id'] != $user->getId()) {
            http_response_code($attempt ? 403 : 404);
            echo $attempt ? 'Forbidden' : 'Checkout attempt not found.';
            return;
        }

        $status = $attempt['status'] ?? '';
        if (in_array($status, ['expired', 'handoff_failed'], true)) {
            $this->checkout->unlockIfAttemptId($id);
            $msg = $status === 'expired' ? 'Your payment hold expired.' : ($attempt['error_message'] ?? 'Payment failed.');
            $this->checkout->setFlash('info', $msg . ' Please retry checkout.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        if ($status === 'paid') {
            $this->checkout->unlockIfAttemptId($id);
        }

        echo View::render('checkout_pending', $viewData);
    }

    public function confirmPendingPayment(int $id): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded(true);
        $user = $this->requireCheckoutUser($this->requireSessionUser()->getId());

        $result = $this->checkout->confirmPendingPayment($id, $user)->toArray();
        $status = $result['status'] ?? 'unknown';

        if (in_array($status, ['paid', 'already_paid'], true)) {
            $this->checkout->unlockIfAttemptId($id);
            $this->checkout->clearPlannerIfUnlocked();
            $this->checkout->setFlash($status === 'paid' ? 'success' : 'info', $result['message'] ?? 'Payment confirmed.');
        } else {
            $this->checkout->setFlash($status === 'forbidden' ? 'error' : 'info', $result['message'] ?? 'Payment confirmation failed.');
        }

        $this->redirect('/checkout/pending/' . $id);
    }

    private function requireSessionUser(): User
    {
        if (!$user = $this->auth->currentUser()) {
            $this->redirect('/login?redirect=' . urlencode(self::CHECKOUT_PATH));
        }

        return $user;
    }

    private function requireCheckoutUser(?int $id = null): User
    {
        $id ??= $this->requireSessionUser()->getId();
        if (!$user = $this->checkout->loadCheckoutUser($id)) {
            $this->auth->logout();
            $this->redirect('/login?redirect=' . urlencode(self::CHECKOUT_PATH));
        }

        return $user;
    }

    private function redirectOutOfStock(array $result): void
    {
        $conflicts = $result['conflicts'] ?? [];
        if (!$conflicts) {
            $this->checkout->setFlash('error', $result['message'] ?? 'Some events are no longer available.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $parts = array_map(static fn($c) => sprintf(
            '%s (req %d, avail %d)',
            $c['event_name'] ?? 'Event',
            $c['requested'] ?? 0,
            $c['available'] ?? 0
        ), $conflicts);
        $this->checkout->setFlash('error', 'Out of stock: ' . implode('; ', $parts) . '.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    private function redirectConfirmError(array $result): void
    {
        $type = ($result['status'] ?? '') === 'handoff_failed' ? 'error' : 'info';
        $this->checkout->setFlash($type, $result['message'] ?? 'Checkout could not be completed.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    private function redirectToPendingAttempt(int $id): void
    {
        $id > 0 && $this->redirect('/checkout/pending/' . $id);
        $this->redirect(self::CHECKOUT_PATH);
    }

    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
