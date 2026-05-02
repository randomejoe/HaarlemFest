<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use App\Controllers\PasswordController;
use App\Services\PasswordResetService;
use App\View;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PasswordControllerTest extends TestCase
{
    private PasswordResetService&MockObject $reset;

    protected function setUp(): void
    {
        $this->reset = $this->createMock(PasswordResetService::class);
        View::setCsrfTokenResolver(static fn(): string => 'test-csrf-token');
    }

    public function test_showForgot_renders_csrf_token(): void
    {
        ob_start();
        (new PasswordController($this->reset))->showForgot();
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('name="csrf_token"', $output);
        $this->assertStringContainsString('value="test-csrf-token"', $output);
    }

    public function test_showReset_renders_csrf_token(): void
    {
        ob_start();
        (new PasswordController($this->reset))->showReset('abc123');
        $output = ob_get_clean() ?: '';

        $this->assertStringContainsString('action="/password/reset/abc123"', $output);
        $this->assertStringContainsString('value="test-csrf-token"', $output);
    }
}
