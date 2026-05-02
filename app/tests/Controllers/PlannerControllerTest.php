<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use App\Controllers\PlannerController;
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
        $this->planner->expects($this->once())
            ->method('getDetailedPlanner')
            ->willReturn([
                'total_quantity' => 2,
                'total_price' => '24.50',
                'is_empty' => false,
                'time_conflicts' => [],
                'items' => [],
            ]);

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
