<?php

use Phinx\Migration\AbstractMigration;

class UpdateUsersAuthFields extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');

        if ($users->hasColumn('password')) {
            $users->renameColumn('password', 'password_hash');
        }

        if (!$users->hasColumn('created_at')) {
            $users->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        }

        if (!$users->hasColumn('password_reset_token')) {
            $users->addColumn('password_reset_token', 'string', ['limit' => 255, 'null' => true]);
        }

        if (!$users->hasColumn('password_reset_expires_at')) {
            $users->addColumn('password_reset_expires_at', 'datetime', ['null' => true]);
        }

        if (!$users->hasIndex(['password_reset_token'])) {
            $users->addIndex(['password_reset_token'], ['unique' => true]);
        }

        $users->update();
    }
}
