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

    private PlannerService $planner;
    private CheckoutService $checkout;
    private AuthService $auth;
    private UserRepository $users;
    private CheckoutRepository $checkoutAttempts;

    public function __construct(
        PlannerService $planner,
        CheckoutService $checkout,
        AuthService $auth,
        UserRepository $users,
        CheckoutRepository $checkoutAttempts
    ) {
        $this->planner = $planner;
        $this->checkout = $checkout;
        $this->auth = $auth;
        $this->users = $users;
        $this->checkoutAttempts = $checkoutAttempts;
    }

    public function show(): void
    {
        $this->releaseExpiredHoldsAndUnlockIfNeeded();
        $this->redirectToPendingAttemptIfLocked();

        $user = $this->requireCheckoutUser();

        $planner = $this->planner->getDetailedPlanner();
        $missingFields = $this->missingRequiredDetails($user);
        $flash = $this->planner->consumeFlash();

        echo View::render('checkout', [
            'planner' => $planner,
            'user' => $user,
            'flash' => $flash,
            'missing_fields' => $missingFields,
            'requires_details' => $missingFields !== [],
            'idempotency_key' => $this->planner->getIdempotencyKey(),
        ]);
    }

    public function saveDetails(): void
    {
        $user = $this->requireCheckoutUser();
        $details = $this->readCheckoutDetails();

        foreach (self::REQUIRED_FIELDS as $field) {
            if ($details[$field] === '') {
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
        $this->releaseExpiredHoldsAndUnlockIfNeeded(true);
        $this->redirectToPendingAttemptIfLocked();

        $user = $this->requireCheckoutUser();

        if ($this->missingRequiredDetails($user) !== []) {
            $this->planner->setFlash('error', 'Please complete your required details before checkout.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $idempotencyKey = trim((string) ($_POST['idempotency_key'] ?? ''));
        $simulateFailure = isset($_POST['simulate_handoff_failure']) && (string) $_POST['simulate_handoff_failure'] === '1';

        $result = $this->checkout->confirmCheckout($user, $idempotencyKey, $simulateFailure);
        $status = (string) ($result['status'] ?? 'unknown');

        if ($status === 'handoff_created' || $status === 'already_pending' || $status === 'already_paid') {
            $attemptId = (int) ($result['attempt_id'] ?? 0);
            if ($attemptId > 0) {
                $this->redirectToPendingAttempt($attemptId);
            }
        }

        if ($status === 'out_of_stock') {
            $this->setOutOfStockFlash((array) ($result['conflicts'] ?? []), (string) ($result['message'] ?? 'Some events are no longer available.'));
            $this->redirect(self::CHECKOUT_PATH);
        }

        $flashType = $status === 'handoff_failed' ? 'error' : 'info';
        $this->planner->setFlash($flashType, (string) ($result['message'] ?? 'Checkout could not be completed.'));

        $this->redirect(self::CHECKOUT_PATH);
    }

    public function pending(int $checkoutAttemptId): void
    {
        $this->releaseExpiredHoldsAndUnlockIfNeeded(true);

        $sessionUser = $this->requireSessionUser();

        if ($checkoutAttemptId <= 0) {
            http_response_code(404);
            echo 'Checkout attempt not found.';
            return;
        }

        $attempt = $this->checkoutAttempts->findById($checkoutAttemptId);
        if ($attempt === null) {
            http_response_code(404);
            echo 'Checkout attempt not found.';
            return;
        }

        if ((int) ($attempt['user_id'] ?? 0) !== $sessionUser->id()) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $status = (string) ($attempt['status'] ?? '');
        if ($status === 'expired') {
            $this->unlockPlannerAttempt($checkoutAttemptId);
            $this->planner->setFlash('info', 'Your payment hold expired. Please confirm checkout again.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        if ($status === 'handoff_failed') {
            $this->unlockPlannerAttempt($checkoutAttemptId);
            $message = (string) ($attempt['error_message'] ?? 'Payment handoff failed. Please retry checkout.');
            $this->planner->setFlash('error', $message);
            $this->redirect(self::CHECKOUT_PATH);
        }

        if ($status === 'paid') {
            $this->unlockPlannerAttempt($checkoutAttemptId);
        }

        $items = $this->checkoutAttempts->findItemsWithEventData($checkoutAttemptId);
        $flash = $this->planner->consumeFlash();

        echo View::render('checkout_pending', [
            'attempt' => $attempt,
            'items' => $items,
            'flash' => $flash,
        ]);
    }

    public function confirmPendingPayment(int $checkoutAttemptId): void
    {
        $this->releaseExpiredHoldsAndUnlockIfNeeded(true);

        $sessionUser = $this->requireSessionUser();

        if ($checkoutAttemptId <= 0) {
            $this->planner->setFlash('error', 'Checkout attempt not found.');
            $this->redirect(self::CHECKOUT_PATH);
        }

        $user = $this->requireCheckoutUser($sessionUser->id());

        $result = $this->checkout->confirmPendingPayment($checkoutAttemptId, $user);
        $status = (string) ($result['status'] ?? 'unknown');

        if ($status === 'paid' || $status === 'already_paid') {
            $this->unlockPlannerAttempt($checkoutAttemptId);

            if (!$this->planner->isLocked()) {
                $this->planner->clear();
            }

            $flashType = $status === 'paid' ? 'success' : 'info';
            $this->planner->setFlash($flashType, (string) ($result['message'] ?? 'Payment confirmed.'));
            $this->redirectToPendingAttempt($checkoutAttemptId);
        }

        $flashType = $status === 'forbidden' ? 'error' : 'info';
        $this->planner->setFlash($flashType, (string) ($result['message'] ?? 'Payment confirmation could not be completed.'));
        $this->redirectToPendingAttempt($checkoutAttemptId);
    }

    private function releaseExpiredHoldsAndUnlockIfNeeded(bool $force = false): void
    {
        $result = $this->checkout->releaseExpiredHoldsIfNeeded($force);

        if ($this->planner->unlockIfExpired((array) ($result['expired_attempt_ids'] ?? []))) {
            $this->planner->setFlash('info', 'Your previous hold expired. Please review and confirm checkout again.');
        }
    }

    private function missingRequiredDetails(User $user): array
    {
        $missing = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            $value = trim((string) match ($field) {
                'first_name' => $user->firstName(),
                'last_name' => $user->lastName(),
                'address' => $user->address(),
                'city' => $user->city(),
                'country' => $user->country(),
                'phone_number' => $user->phoneNumber(),
            });
            if ($value === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function requireSessionUser(): User
    {
        $sessionUser = $this->auth->currentUser();
        if ($sessionUser === null) {
            $this->redirectToCheckoutLogin();
        }

        return $sessionUser;
    }

    private function requireCheckoutUser(?int $userId = null): User
    {
        if ($userId === null) {
            $sessionUser = $this->requireSessionUser();
            $userId = $sessionUser->id();
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            $this->auth->logout();
            $this->redirectToCheckoutLogin();
        }

        return $user;
    }

    private function redirectToCheckoutLogin(): void
    {
        $this->redirect('/login?redirect=' . urlencode(self::CHECKOUT_PATH));
    }

    private function redirectToPendingAttemptIfLocked(): void
    {
        $lockedAttemptId = $this->planner->getLockedCheckoutAttemptId();
        if ($lockedAttemptId !== null) {
            $this->redirectToPendingAttempt($lockedAttemptId);
        }
    }

    private function redirectToPendingAttempt(int $checkoutAttemptId): void
    {
        $this->redirect('/checkout/pending/' . $checkoutAttemptId);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function readCheckoutDetails(): array
    {
        return [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'country' => trim((string) ($_POST['country'] ?? '')),
            'phone_number' => trim((string) ($_POST['phone_number'] ?? '')),
        ];
    }

    private function setOutOfStockFlash(array $conflicts, string $fallbackMessage): void
    {
        $parts = [];

        foreach ($conflicts as $conflict) {
            $parts[] = sprintf(
                '%s (requested %d, available %d)',
                (string) ($conflict['event_name'] ?? 'Event'),
                (int) ($conflict['requested'] ?? 0),
                (int) ($conflict['available'] ?? 0)
            );
        }

        if ($parts !== []) {
            $this->planner->setFlash('error', 'Out of stock: ' . implode('; ', $parts) . '.');
            return;
        }

        $this->planner->setFlash('error', $fallbackMessage);
    }

    private function unlockPlannerAttempt(int $checkoutAttemptId): void
    {
        if ($this->planner->getLockedCheckoutAttemptId() === $checkoutAttemptId) {
            $this->planner->unlock();
        }
    }
}
