<?php

namespace App\Controllers;

use App\Models\User;
use App\Repositories\CheckoutRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CheckoutService;
use App\Services\PlannerService;
use App\View;

class CheckoutController
{
    private const REQUIRED_FIELDS = ['first_name', 'last_name', 'address', 'city', 'country', 'phone_number'];
    private const CHECKOUT_PATH = '/checkout';

    public function __construct(
        private PlannerService $planner,
        private CheckoutService $checkout,
        private AuthService $auth,
        private UserRepository $users,
        private CheckoutRepository $checkoutAttempts
    ) {}

    public function show(): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded();
        $lockedId = $this->planner->getLockedCheckoutAttemptId();
        $lockedId && $this->redirect('/checkout/pending/' . $lockedId);

        $user = $this->requireCheckoutUser();
        $missing = $this->missingRequiredDetails($user);

        echo View::render('checkout', [
            'planner' => $this->planner->getDetailedPlanner(),
            'user' => $user,
            'flash' => $this->planner->consumeFlash(),
            'missing_fields' => $missing,
            'requires_details' => !empty($missing),
            'idempotency_key' => $this->planner->getIdempotencyKey(),
        ]);
    }

    public function saveDetails(): void
    {
        $user = $this->requireCheckoutUser();
        $details = array_map('trim', $_POST);

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($details[$field])) {
                $this->planner->setFlash('error', 'Please complete all required checkout details.');
                $this->redirect(self::CHECKOUT_PATH);
            }
        }

        $this->users->updateCheckoutDetails($user->id(), $details);
        $this->planner->setFlash('success', 'Your checkout details were saved.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    public function confirm(): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded(true);
        $lockedId = $this->planner->getLockedCheckoutAttemptId();
        $lockedId && $this->redirect('/checkout/pending/' . $lockedId);

        $user = $this->requireCheckoutUser();
        if ($this->missingRequiredDetails($user)) {
            $this->planner->setFlash('error', 'Please complete your required details before checkout.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $result = $this->checkout->confirmCheckout(
            $user,
            trim($_POST['idempotency_key'] ?? ''),
            !empty($_POST['simulate_handoff_failure'])
        )->toArray();

        match ($result['status'] ?? 'unknown') {
            'handoff_created', 'already_pending', 'already_paid' => $this->redirectToPendingAttempt($result['attempt_id'] ?? 0),
            'out_of_stock' => $this->handleOutOfStock($result),
            default => $this->handleConfirmError($result)
        };
    }

    public function pending(int $id): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded(true);
        $user = $this->requireSessionUser();
        $attempt = $this->checkoutAttempts->findById($id);

        if (!$attempt || $attempt['user_id'] != $user->id()) {
            http_response_code($attempt ? 403 : 404);
            echo $attempt ? 'Forbidden' : 'Checkout attempt not found.';
            return;
        }

        $status = $attempt['status'] ?? '';
        if (in_array($status, ['expired', 'handoff_failed'])) {
            $this->unlockPlannerAttempt($id);
            $msg = $status === 'expired' ? 'Your payment hold expired.' : ($attempt['error_message'] ?? 'Payment failed.');
            $this->planner->setFlash('info', $msg . ' Please retry checkout.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $status === 'paid' && $this->unlockPlannerAttempt($id);

        echo View::render('checkout_pending', [
            'attempt' => $attempt,
            'items' => $this->checkoutAttempts->findItemsWithEventData($id),
            'flash' => $this->planner->consumeFlash(),
        ]);
    }

    public function confirmPendingPayment(int $id): void
    {
        $this->checkout->releaseExpiredHoldsIfNeeded(true);
        $user = $this->requireCheckoutUser($this->requireSessionUser()->id());

        $result = $this->checkout->confirmPendingPayment($id, $user)->toArray();
        $status = $result['status'] ?? 'unknown';

        if (in_array($status, ['paid', 'already_paid'])) {
            $this->unlockPlannerAttempt($id);
            !$this->planner->isLocked() && $this->planner->clear();
            $this->planner->setFlash($status === 'paid' ? 'success' : 'info', $result['message'] ?? 'Payment confirmed.');
        } else {
            $this->planner->setFlash($status === 'forbidden' ? 'error' : 'info', $result['message'] ?? 'Payment confirmation failed.');
        }

        $this->redirect('/checkout/pending/' . $id);
    }

    private function missingRequiredDetails(User $user): array
    {
        $missing = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            $method = match ($field) {
                'first_name' => 'firstName',
                'last_name' => 'lastName',
                'phone_number' => 'phoneNumber',
                default => $field
            };
            trim((string) $user->$method()) === '' && $missing[] = $field;
        }
        return $missing;
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
        $id ??= $this->requireSessionUser()->id();
        if (!$user = $this->users->findById($id)) {
            $this->auth->logout();
            $this->redirect('/login?redirect=' . urlencode(self::CHECKOUT_PATH));
        }
        return $user;
    }

    private function handleOutOfStock(array $result): void
    {
        $conflicts = $result['conflicts'] ?? [];
        if (!$conflicts) {
            $this->planner->setFlash('error', $result['message'] ?? 'Some events are no longer available.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $parts = array_map(fn($c) => sprintf(
            '%s (req %d, avail %d)',
            $c['event_name'] ?? 'Event',
            $c['requested'] ?? 0,
            $c['available'] ?? 0
        ), $conflicts);
        $this->planner->setFlash('error', 'Out of stock: ' . implode('; ', $parts) . '.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    private function handleConfirmError(array $result): void
    {
        $type = ($result['status'] ?? '') === 'handoff_failed' ? 'error' : 'info';
        $this->planner->setFlash($type, $result['message'] ?? 'Checkout could not be completed.');
        $this->redirect(self::CHECKOUT_PATH);
    }

    private function redirectToPendingAttempt(int $id): void
    {
        $id > 0 && $this->redirect('/checkout/pending/' . $id);
        $this->redirect(self::CHECKOUT_PATH);
    }

    private function unlockPlannerAttempt(int $id): void
    {
        $this->planner->unlockIfAttemptId($id);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
