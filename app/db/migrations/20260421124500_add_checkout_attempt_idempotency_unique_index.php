<?php

use Phinx\Migration\AbstractMigration;

class AddCheckoutAttemptIdempotencyUniqueIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "DELETE ca1
             FROM checkout_attempts ca1
             INNER JOIN checkout_attempts ca2
               ON ca1.idempotency_key = ca2.idempotency_key
              AND ca1.checkout_attempt_id > ca2.checkout_attempt_id
             WHERE ca1.status IN ('initiated', 'handoff_failed', 'expired')"
        );

        $this->execute(
            'ALTER TABLE checkout_attempts
             ADD UNIQUE INDEX uq_checkout_attempts_idempotency_key (idempotency_key)'
        );
    }

    public function down(): void
    {
        $this->execute(
            'ALTER TABLE checkout_attempts
             DROP INDEX uq_checkout_attempts_idempotency_key'
        );
    }
}
