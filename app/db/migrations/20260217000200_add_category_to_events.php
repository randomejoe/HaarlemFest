<?php

use Phinx\Migration\AbstractMigration;

class AddCategoryToEvents extends AbstractMigration
{
    public function change()
    {
        $events = $this->table('events');

        if (!$events->hasColumn('category')) {
            $events->addColumn('category', 'string', ['limit' => 100, 'null' => true]);
            $events->update();
        }
    }
}
