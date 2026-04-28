<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\CheckoutRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketHoldRepository;
use App\Services\CheckoutHoldManager;
use App\Services\DateTimeFormatter;

require __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = Connection::get();
$resultObject = (new CheckoutHoldManager(
    new TicketHoldRepository($pdo),
    new CheckoutRepository($pdo),
    new EventRepository($pdo),
    new DateTimeFormatter(),
    $pdo
))->releaseExpiredHolds();
$result = $resultObject->toArray();

fwrite(STDOUT, json_encode([
    'released_count' => (int) ($result['released_count'] ?? 0),
    'expired_attempt_ids' => array_values(array_map('intval', (array) ($result['expired_attempt_ids'] ?? []))),
], JSON_PRETTY_PRINT) . PHP_EOL);
