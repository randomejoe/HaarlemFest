<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

class DropUnusedLegacyTables extends AbstractMigration
{
	public function up(): void
	{
		$tablesToDrop = [
			'reservations',
			'cart_items',
			'carts',
			'access_passes',
		];

		foreach ($tablesToDrop as $tableName) {
			if ($this->hasTable($tableName)) {
				$this->table($tableName)->drop()->save();
			}
		}
	}

	public function down(): void
	{
		throw new IrreversibleMigrationException('This migration drops legacy tables and cannot be reversed automatically.');
	}
}
