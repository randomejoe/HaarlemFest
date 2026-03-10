<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeComponentStructure extends AbstractMigration
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
        $table = $this->table('page_content');

        $table->dropForeignKey('component_id')
        ->removeColumn('component_id')
        ->addColumn('component_name', 'string')
        ->addColumn('data', 'json', ['null' => true])
        ->save();

        $this->table('page_content_variables')->drop()->save();
        $this->table('page_component_variable_keys')->drop()->save();
        $this->table('page_components')->drop()->save();
    }
}
