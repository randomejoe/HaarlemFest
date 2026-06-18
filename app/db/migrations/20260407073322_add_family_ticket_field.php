<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFamilyTicketField extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $table = $this->table('checkout_attempt_items');

        $table->addColumn('family_ticket', 'boolean', [
            'null' => true,
            'default' => null,
        ])->update();

        $table = $this->table('tickets');

        $table->addColumn('family_ticket', 'boolean', [
            'null' => true,
            'default' => null,
        ])->update();
    }

    public function down(): void
    {
        $table = $this->table('checkout_attempt_items');

        $table->removeColumn('family_ticket')->update();

        $table = $this->table('tickets');

        $table->removeColumn('family_ticket')->update();
    }
}
