<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

final class DropUnusedCheckoutPlannerTables extends AbstractMigration
{
    public function up(): void
    {
        $tablesToDrop = [
            'ticket_holds',
            'checkout_attempt_items',
            'checkout_attempts',
        ];

        foreach ($tablesToDrop as $tableName) {
            if ($this->hasTable($tableName)) {
                $this->table($tableName)->drop()->save();
            }
        }
    }

    public function down(): void
    {
        throw new IrreversibleMigrationException('This migration drops unused checkout planner tables and cannot be reversed automatically.');
    }
}
