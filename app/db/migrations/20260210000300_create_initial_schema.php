<?php

use Phinx\Migration\AbstractMigration;

class CreateInitialSchema extends AbstractMigration
{
	public function change()
	{
		// users
		$users = $this->table('users', ['id' => 'user_id']);
		$users->addColumn('role', 'enum', ['values' => ['admin', 'user']])
			->addColumn('username', 'string', ['limit' => 255])
			->addColumn('email', 'string', ['limit' => 255])
			->addColumn('password', 'string', ['limit' => 255])
			->addIndex(['username'], ['unique' => true])
			->addIndex(['email'], ['unique' => true])
			->create();

		// venues
		$venues = $this->table('venues', ['id' => 'venue_id']);
		$venues->addColumn('location', 'string', ['limit' => 255])
			->addColumn('capacity', 'integer')
			->create();

		// events
		$events = $this->table('events', ['id' => 'event_id']);
		$events->addColumn('name', 'string', ['limit' => 255])
			->addColumn('location', 'string', ['limit' => 255, 'null' => true])
			->addColumn('start_time', 'datetime')
			->addColumn('end_time', 'datetime')
			->addColumn('venue_id', 'integer', ['null' => true, 'signed' => false])
			->addColumn('ticket_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
			->addColumn('ticket_amount', 'integer', ['null' => true])
			->addColumn('language', 'string', ['limit' => 255, 'null' => true])
			->addColumn('description', 'text', ['null' => true])
			->addIndex(['venue_id'])
			->addForeignKey('venue_id', 'venues', 'venue_id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
			->create();

		// invoices
		$invoices = $this->table('invoices', ['id' => 'invoice_id']);
		$invoices->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
			->addColumn('total_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
			->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addForeignKey('user_id', 'users', 'user_id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
			->create();

		// tickets
		$tickets = $this->table('tickets', ['id' => 'ticket_id']);
		$tickets->addColumn('event_id', 'integer', ['null' => true, 'signed' => false])
			->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
			->addColumn('invoice_id', 'integer', ['null' => true, 'signed' => false])
			->addColumn('ticket_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
			->addForeignKey('event_id', 'events', 'event_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
			->addForeignKey('user_id', 'users', 'user_id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
			->addForeignKey('invoice_id', 'invoices', 'invoice_id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
			->create();

		// access_passes
		$access = $this->table('access_passes', ['id' => 'access_pass_id']);
		$access->addColumn('user_id', 'integer', ['signed' => false])
			->addColumn('issued_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addForeignKey('user_id', 'users', 'user_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
			->create();

		// pages
		$pages = $this->table('pages', ['id' => 'page_id']);
		$pages->addColumn('title', 'string', ['limit' => 255])
			->create();

		// page_components
		$components = $this->table('page_components', ['id' => 'component_id']);
		$components->addColumn('content', 'text')
			->addColumn('variable_keys', 'json', ['null' => true])
			->create();

		// page_content
		$pageContent = $this->table('page_content', ['id' => 'content_id']);
		$pageContent->addColumn('component_id', 'integer', ['signed' => false])
			->addColumn('page_id', 'integer', ['signed' => false])
			->addColumn('variables', 'json', ['null' => true])
			->addForeignKey('component_id', 'page_components', 'component_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
			->addForeignKey('page_id', 'pages', 'page_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
			->create();
	}
}
