<?php

namespace App\Services\Interfaces;

interface ISessionManager
{
    public function getPlannerToken(): string;

    public function getPlannerState(): array;

    public function setPlannerState(array $planner): void;

    public function setFlash(string $type, string $message): void;

    public function consumeFlash(): ?array;

    public function shouldRunExpiryCleanup(int $cooldownSeconds): bool;

    public function markExpiryCleanupRun(?int $timestamp = null): void;

    public function resetExpiryCleanupRun(): void;

    public function generateToken(): string;
}
