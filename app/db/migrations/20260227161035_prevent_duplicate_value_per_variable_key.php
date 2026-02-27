<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PreventDuplicateValuePerVariableKey extends AbstractMigration
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
    public function change(): void
    {
        $table = $this->table('page_content_variables');

        $table->addIndex(
            ['content_id', 'variable_key_id'], 
            ['unique' => true, 'name' => 'unique_value_per_key']
        )->update();
    }
}

