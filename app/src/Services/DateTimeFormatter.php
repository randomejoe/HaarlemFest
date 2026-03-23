<?php

declare(strict_types=1);

namespace App\Services;

final class DateTimeFormatter
{
    public function formatTimestamp(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function currentTimestamp(): int
    {
        return time();
    }

    public function currentDateTime(): string
    {
        return $this->formatTimestamp($this->currentTimestamp());
    }

    public function addSeconds(int $timestamp, int $seconds): string
    {
        return $this->formatTimestamp($timestamp + $seconds);
    }
}

