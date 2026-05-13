<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\PlannerSummary;

final class PlannerViewModel
{
    /**
     * @param array<int, string> $timeConflicts
     * @param array<int, array<string, mixed>> $timeConflictPairs
     */
    public function __construct(
        private PlannerSummary $summary,
        private array $timeConflicts = [],
        private array $timeConflictPairs = [],
        private ?array $flash = null
    ) {
    }

    public function summary(): PlannerSummary
    {
        return $this->summary;
    }

    public function flash(): ?array
    {
        return $this->flash;
    }

    public function toArray(): array
    {
        $summary = $this->summary->toArray();

        return array_merge($summary, [
            'time_conflicts' => $this->timeConflicts,
            'time_conflict_pairs' => $this->timeConflictPairs,
        ]);
    }
}
