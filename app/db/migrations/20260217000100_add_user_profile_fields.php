<?php

use Phinx\Migration\AbstractMigration;

class AddUserProfileFields extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');

        if (!$users->hasColumn('first_name')) {
            $users->addColumn('first_name', 'string', ['limit' => 100, 'null' => true]);
        }

        if (!$users->hasColumn('last_name')) {
            $users->addColumn('last_name', 'string', ['limit' => 100, 'null' => true]);
        }

        if (!$users->hasColumn('address')) {
            $users->addColumn('address', 'string', ['limit' => 255, 'null' => true]);
        }

        if (!$users->hasColumn('city')) {
            $users->addColumn('city', 'string', ['limit' => 120, 'null' => true]);
        }

        if (!$users->hasColumn('country')) {
            $users->addColumn('country', 'string', ['limit' => 120, 'null' => true]);
        }

        if (!$users->hasColumn('phone_number')) {
            $users->addColumn('phone_number', 'string', ['limit' => 40, 'null' => true]);
        }

        $users->update();
    }
}
