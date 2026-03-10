<?php

namespace App\Controllers;

use App\Services\PlannerService;
use App\View;
use InvalidArgumentException;
use RuntimeException;

class PlannerController
{
    private PlannerService $planner;

    public function __construct(PlannerService $planner)
    {
        $this->planner = $planner;
    }

    public function show(): void
    {
        $planner = $this->planner->getDetailedPlanner();
        $flash = $this->planner->consumeFlash();

        echo View::render('planner', [
            'planner' => $planner,
            'flash' => $flash,
        ]);
    }

    public function addItem(): void
    {
        $returnTo = $this->resolveReturnTo((string) ($_POST['return_to'] ?? '/planner'));
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);

        try {
            if ($eventId <= 0) {
                throw new InvalidArgumentException('Select a valid event before adding it to your planner.');
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Quantity must be greater than zero.');
            }

            $this->planner->addItem($eventId, $quantity);
            $this->planner->setFlash('success', 'Event added to your personal planner.');
        } catch (RuntimeException|InvalidArgumentException $e) {
            $this->planner->setFlash('error', $e->getMessage());
        }

        header('Location: ' . $returnTo);
        exit;
    }

    public function updateItemQuantity(int $eventId): void
    {
        $quantity = (int) ($_POST['quantity'] ?? 0);

        try {
            $this->planner->updateItemQuantity($eventId, $quantity);
            $this->planner->setFlash('success', 'Planner quantity updated.');
        } catch (RuntimeException|InvalidArgumentException $e) {
            $this->planner->setFlash('error', $e->getMessage());
        }

        header('Location: /planner');
        exit;
    }

    public function removeItem(int $eventId): void
    {
        try {
            $this->planner->removeItem($eventId);
            $this->planner->setFlash('success', 'Event removed from your planner.');
        } catch (RuntimeException|InvalidArgumentException $e) {
            $this->planner->setFlash('error', $e->getMessage());
        }

        header('Location: /planner');
        exit;
    }

    public function clear(): void
    {
        try {
            $this->planner->clear();
            $this->planner->setFlash('success', 'Your personal planner was cleared.');
        } catch (RuntimeException $e) {
            $this->planner->setFlash('error', $e->getMessage());
        }

        header('Location: /planner');
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
}
