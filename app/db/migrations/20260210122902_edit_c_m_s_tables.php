<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EditCMSTables extends AbstractMigration
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

        $table = $this->table('page_component_variable_keys', ['id' => 'page_component_variable_key_id']);
        $table
        ->addColumn('component_id', 'integer', ['signed' => false])
        ->addColumn('variable_key', 'string')
        ->addIndex(['component_id'])
        ->addForeignKey('component_id', 'page_components', 'component_id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION'
        ])
        ->create();

        $table = $this->table('page_content_variables', ['id' => 'page_content_variable_id']);
        $table
        ->addColumn('content_id', 'integer',['signed' => false])
        ->addColumn('variable_key_id', 'integer',['signed' => false])
        ->addColumn('value', 'string', ['null' => true])
        ->addIndex(['content_id','variable_key_id'])
        ->addForeignKey('content_id', 'page_content', 'content_id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION'
        ])
        ->addForeignKey('variable_key_id', 'page_component_variable_keys', 'page_component_variable_key_id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE'
        ])
        ->create();

        $this->table('page_components')
        ->removeColumn('variable_keys')
        ->update();

        $this->table('page_content')
        ->removeColumn('variables')
        ->update();
    }
}
