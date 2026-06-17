<?php

namespace App\Controllers;

use App\Services\Interfaces\IPlannerService;
use App\View;
use App\Models\FlashType;
use App\ViewModels\PlannerViewModel;
use InvalidArgumentException;
use RuntimeException;

class PlannerController
{
    private IPlannerService $planner;

    public function __construct(IPlannerService $planner)
    {
        $this->planner = $planner;
    }

    public function show(): void
    {
        $this->planner->pruneUnavailableItems();
        $summary = $this->planner->getPlannerSummary();
        $flash = $this->planner->consumeFlash();

        $vm = new PlannerViewModel(
            $summary,
            $summary->timeConflicts(),
            $summary->timeConflictPairs(),
            $flash
        );

        echo View::render('planner', [
            'planner' => $vm->toArray(),
            'flash' => $flash,
        ]);
    }

    public function addItem(): void
    {
        $returnTo = $this->resolveReturnTo((string) ($_POST['return_to'] ?? '/planner'));
        $eventIds = $this->resolveEventIds($_POST['event_ids'] ?? null, (int) ($_POST['event_id'] ?? 0));
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $isAjax = $this->isAjaxRequest();
        $success = true;
        $message = '';

        try {
            if ($eventIds === []) {
                throw new InvalidArgumentException('Select a valid event before adding it to your planner.');
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Quantity must be greater than zero.');
            }

            if (count($eventIds) === 1) {
                $this->planner->addItem($eventIds[0], $quantity, $_POST['familyTicket'] ?? null);
            } else {
                $this->planner->addItems($this->buildPlannerRows($eventIds, $_POST['familyTicket'] ?? null), $quantity);
            }
        } catch (RuntimeException | InvalidArgumentException $e) {
            $success = false;
            $message = $e->getMessage();
            $this->planner->setFlash(FlashType::Error, $message);
        }

        if ($isAjax) {
            $planner = $this->planner->getDetailedPlanner();
            $this->sendPlannerJsonResponse($success, $message, $planner);
        }

        header('Location: ' . $returnTo);
        exit;
    }

    public function updateItemQuantity(int $eventId): void
    {
        $isAjax = $this->isAjaxRequest();
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $success = true;
        $message = 'Planner quantity updated.';

        try {
            $this->planner->updateItemQuantity($eventId, $quantity);
        } catch (RuntimeException | InvalidArgumentException $e) {
            $success = false;
            $message = $e->getMessage();
        }

        if ($isAjax) {
            $planner = $this->planner->getDetailedPlanner();
            $item = null;

            foreach ((array) ($planner['items'] ?? []) as $plannerItem) {
                if ((int) ($plannerItem['event_id'] ?? 0) === $eventId) {
                    $item = $plannerItem;
                    break;
                }
            }

            $this->sendPlannerJsonResponse($success, $message, $planner, [
                'event_id' => $eventId,
                'item' => $item,
                'action' => 'quantity',
            ]);
        }

        $this->planner->setFlash($success ? FlashType::Success : FlashType::Error, $message);

        header('Location: /planner');
        exit;
    }

    public function removeItem(int $eventId): void
    {
        $isAjax = $this->isAjaxRequest();
        $success = true;
        $message = 'Event removed from your planner.';

        try {
            $this->planner->removeItem($eventId);
        } catch (RuntimeException | InvalidArgumentException $e) {
            $success = false;
            $message = $e->getMessage();
        }

        if ($isAjax) {
            $planner = $this->planner->getDetailedPlanner();
            $this->sendPlannerJsonResponse($success, $message, $planner, [
                'event_id' => $eventId,
                'action' => 'remove',
            ]);
        }

        $this->planner->setFlash($success ? FlashType::Success : FlashType::Error, $message);

        header('Location: /planner');
        exit;
    }

    public function clear(): void
    {
        $isAjax = $this->isAjaxRequest();
        $success = true;
        $message = 'Your personal planner was cleared.';

        try {
            $this->planner->clear();
        } catch (RuntimeException $e) {
            $success = false;
            $message = $e->getMessage();
        }

        if ($isAjax) {
            $planner = $this->planner->getDetailedPlanner();
            $this->sendPlannerJsonResponse($success, $message, $planner, [
                'action' => 'clear',
            ]);
        }

        $this->planner->setFlash($success ? FlashType::Success : FlashType::Error, $message);

        header('Location: /planner');
        exit;
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return strcasecmp($requestedWith, 'XMLHttpRequest') === 0;
    }

    private function sendPlannerJsonResponse(bool $success, string $message, array $planner, array $payload = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($success ? 200 : 422);

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'planner' => [
                'total_quantity' => (int) ($planner['total_quantity'] ?? 0),
                'total_price' => (string) ($planner['total_price'] ?? '0.00'),
                'is_empty' => (bool) ($planner['is_empty'] ?? false),
                'time_conflicts' => (array) ($planner['time_conflicts'] ?? []),
            ],
            'payload' => $payload,
        ]);
        exit;
    }

    private function resolveReturnTo(string $returnTo): string
    {
        $returnTo = trim($returnTo);

        if ($returnTo === '' || $returnTo[0] !== '/') {
            return '/planner';
        }

        if (str_starts_with($returnTo, '//')) {
            return '/planner';
        }

        if (str_contains($returnTo, "\n") || str_contains($returnTo, "\r")) {
            return '/planner';
        }

        return $returnTo;
    }

    private function resolveEventIds($rawEventIds, int $fallbackEventId): array
    {
        if (is_array($rawEventIds)) {
            $eventIds = array_values(array_unique(array_map('intval', $rawEventIds)));
            $eventIds = array_values(array_filter($eventIds, static fn(int $eventId): bool => $eventId > 0));

            if ($eventIds !== []) {
                return $eventIds;
            }
        }

        return $fallbackEventId > 0 ? [$fallbackEventId] : [];
    }

    private function buildPlannerRows(array $eventIds, ?string $familyTicket): array
    {
        $isFamilyTicket = in_array(strtolower((string) $familyTicket), ['1', 'true', 'yes', 'on'], true);
        $rows = [];

        foreach ($eventIds as $eventId) {
            $rows[] = [
                'event_id' => (int) $eventId,
                'familyTicket' => $isFamilyTicket,
            ];
        }

        return $rows;
    }
}
