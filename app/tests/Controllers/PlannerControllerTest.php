<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use App\Controllers\PlannerController;
use App\Models\Event;
use App\Models\PlannerSummary;
use App\Services\Interfaces\IPlannerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PlannerControllerTest extends TestCase
{
    private IPlannerService&MockObject $planner;

    protected function setUp(): void
    {
        $this->planner = $this->createMock(IPlannerService::class);
        $_SESSION = [];
    }

    public function test_show_renders_planner_summary_and_flash_message(): void
    {
        $event = new Event(1, 'Jazz Night', null, '2026-07-01 20:00:00', '2026-07-01 22:00:00', 12.25, 100, null, 'jazz', 'Venue', null, null);
        $summary = PlannerSummary::fromRawItems(
            [1 => ['quantity' => 2, 'familyTicket' => false]],
            [1 => $event]
        );

        $this->planner->expects($this->once())
            ->method('getPlannerSummary')
            ->willReturn($summary);

        $this->planner->expects($this->once())
            ->method('consumeFlash')
            ->willReturn([
                'type' => 'success',
                'message' => 'Planner saved.',
            ]);

        ob_start();
        (new PlannerController($this->planner))->show();
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('Your Planner', $output);
        $this->assertStringContainsString('24.50', $output);
        $this->assertStringContainsString('Planner saved.', $output);
    }
}
