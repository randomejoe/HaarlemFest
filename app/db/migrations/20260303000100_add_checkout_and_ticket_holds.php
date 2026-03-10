<?php

use Phinx\Migration\AbstractMigration;

class AddCheckoutAndTicketHolds extends AbstractMigration
{
    public function change()
    {
        $attempts = $this->table('checkout_attempts', ['id' => 'checkout_attempt_id']);
        $attempts
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('planner_token', 'string', ['limit' => 64])
            ->addColumn('status', 'enum', ['values' => ['initiated', 'handoff_created', 'handoff_failed', 'expired']])
            ->addColumn('total_price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'EUR'])
            ->addColumn('hold_expires_at', 'datetime')
            ->addColumn('idempotency_key', 'string', ['limit' => 64])
            ->addColumn('payment_provider', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('payment_reference', 'string', ['limit' => 191, 'null' => true])
            ->addColumn('error_code', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('error_message', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['planner_token'])
            ->addIndex(['hold_expires_at'])
            ->addIndex(['idempotency_key'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'user_id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();

        $items = $this->table('checkout_attempt_items', ['id' => 'checkout_attempt_item_id']);
        $items
            ->addColumn('checkout_attempt_id', 'integer', ['signed' => false])
            ->addColumn('event_id', 'integer', ['signed' => false])
            ->addColumn('quantity', 'integer', ['signed' => false])
            ->addColumn('unit_price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('line_total', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addIndex(['checkout_attempt_id', 'event_id'], ['unique' => true])
            ->addForeignKey('checkout_attempt_id', 'checkout_attempts', 'checkout_attempt_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('event_id', 'events', 'event_id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->create();

        $holds = $this->table('ticket_holds', ['id' => 'ticket_hold_id']);
        $holds
            ->addColumn('checkout_attempt_id', 'integer', ['signed' => false])
            ->addColumn('event_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('planner_token', 'string', ['limit' => 64])
            ->addColumn('quantity', 'integer', ['signed' => false])
            ->addColumn('status', 'enum', ['values' => ['active', 'transferred', 'released']])
            ->addColumn('expires_at', 'datetime')
            ->addColumn('release_reason', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('released_at', 'datetime', ['null' => true])
            ->addIndex(['event_id'])
            ->addIndex(['planner_token'])
            ->addIndex(['expires_at'])
            ->addForeignKey('checkout_attempt_id', 'checkout_attempts', 'checkout_attempt_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('event_id', 'events', 'event_id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'user_id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
