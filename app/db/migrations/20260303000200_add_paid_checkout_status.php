<?php

use Phinx\Migration\AbstractMigration;

class AddPaidCheckoutStatus extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "ALTER TABLE checkout_attempts
             MODIFY COLUMN status ENUM('initiated','handoff_created','handoff_failed','expired','paid') NOT NULL"
        );
    }

    public function down(): void
    {
        $this->execute(
            "UPDATE checkout_attempts
             SET status = 'handoff_created'
             WHERE status = 'paid'"
        );

        $this->execute(
            "ALTER TABLE checkout_attempts
             MODIFY COLUMN status ENUM('initiated','handoff_created','handoff_failed','expired') NOT NULL"
        );
    }
}
