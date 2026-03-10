<?php

use Phinx\Migration\AbstractMigration;

class AddVerificationCodeToTickets extends AbstractMigration
{
    public function change()
    {
        $tickets = $this->table('tickets');

        if (!$tickets->hasColumn('verification_code')) {
            $tickets->addColumn('verification_code', 'string', ['limit' => 64, 'null' => true]);
            $tickets->update();
        }

        if (!$tickets->hasIndex(['verification_code'])) {
            $tickets->addIndex(['verification_code'], ['unique' => true]);
            $tickets->update();
        }
    }
}
