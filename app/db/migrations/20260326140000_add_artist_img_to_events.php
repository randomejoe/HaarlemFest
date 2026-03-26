<?php

use Phinx\Migration\AbstractMigration;

class AddArtistImgToEvents extends AbstractMigration
{
	public function change()
	{
		$events = $this->table('events');
		$events->addColumn('artist_img', 'string', ['limit' => 255, 'null' => true])
			->update();
	}
}
