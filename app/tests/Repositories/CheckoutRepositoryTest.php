<?php

declare(strict_types=1);

namespace App\Tests\Repositories;

use App\Repositories\CheckoutRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CheckoutRepositoryTest extends TestCase
{
    public function test_findEventForUpdate_uses_real_ticket_amount_column_with_lock(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id' => 12])
            ->willReturn(true);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'event_id' => 12,
                'name' => 'Jazz Night',
                'available_tickets' => 4,
                'ticket_price' => '12.50',
            ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->callback(
                fn(string $sql): bool => str_contains($sql, 'ticket_amount AS available_tickets')
                    && str_contains($sql, 'FOR UPDATE')
            ))
            ->willReturn($stmt);

        $result = (new CheckoutRepository($pdo))->findEventForUpdate(12);

        $this->assertSame(4, $result['available_tickets']);
    }

    public function test_decrementStock_updates_ticket_amount(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['quantity' => 2, 'id' => 12])
            ->willReturn(true);

        $pdo = $this->mockPdoExpectingSql('SET ticket_amount = ticket_amount - :quantity', $stmt);

        (new CheckoutRepository($pdo))->decrementStock(12, 2);
    }

    private function mockPdoExpectingSql(string $needle, PDOStatement $stmt): PDO&MockObject
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->callback(fn(string $sql): bool => str_contains($sql, $needle)))
            ->willReturn($stmt);

        return $pdo;
    }
}
