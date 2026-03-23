<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckoutItem;
use App\Services\Results\CheckoutResult;

final class CheckoutValidationService
{
    public function __construct(
        private PlannerService $planner,
    ) {
    }

    public function isValidIdempotencyKey(string $postedKey): bool
    {
        if ($postedKey === '') {
            return false;
        }

        return hash_equals($this->planner->getIdempotencyKey(), $postedKey);
    }

    /**
     * Inspect planner state and prepare the checkout payload.
     *
     * @return array{
     *     planner: array,
     *     items: CheckoutItem[],
     *     error: ?CheckoutResult
     * }
     */
    public function prepareCheckoutPayload(): array
    {
        $planner = $this->planner->getDetailedPlanner();

        if ((bool) ($planner['is_empty'] ?? false)) {
            return [
                'planner' => $planner,
                'items' => [],
                'error' => new CheckoutResult(
                    'planner_empty',
                    'Your planner is empty.'
                ),
            ];
        }

        if ((bool) ($planner['has_invalid_items'] ?? false)) {
            return [
                'planner' => $planner,
                'items' => [],
                'error' => new CheckoutResult(
                    'planner_invalid',
                    'Remove unavailable events from your planner before checkout.'
                ),
            ];
        }

        $items = $this->extractValidPlannerItems($planner);

        if ($items === []) {
            return [
                'planner' => $planner,
                'items' => [],
                'error' => new CheckoutResult(
                    'planner_invalid',
                    'No valid planner items were found.'
                ),
            ];
        }

        return [
            'planner' => $planner,
            'items' => $items,
            'error' => null,
        ];
    }

    /**
     * Return only the valid planner-item arrays, sorted by event_id for stable DB ordering.
     *
     * @param  array $planner  Return value of PlannerService::getDetailedPlanner()
     * @return array[]
     */
    private function extractValidPlannerItems(array $planner): array
    {
        $items = array_values(array_filter(
            (array) ($planner['items'] ?? []),
            static fn(array $item): bool => (bool) ($item['is_valid'] ?? false)
        ));

        usort($items, static fn(array $a, array $b): int => $a['event_id'] <=> $b['event_id']);

        return $items;
    }
}

