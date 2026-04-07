<?php

use Phinx\Migration\AbstractMigration;

class BackfillEventArtistImages extends AbstractMigration
{
	public function up(): void
	{
		$this->execute(
			"UPDATE events
			SET artist_img = CASE event_id
				WHEN 1 THEN 'aedcc88dfad3404a84ae66629db05f1d.jpg'
				WHEN 2 THEN 'f2cf143b46c4a16241df184908c61007.jpeg'
				WHEN 3 THEN '5e791412039465777ee38fe69493ab84.jpeg'
				WHEN 4 THEN '16b3025e219b66ee59dd69ce83b0cf4a.png'
				WHEN 5 THEN '256594c70b232c1804a7ffc8f3afce59.jpeg'
				WHEN 6 THEN '96d1b2f890f8f0992554f1b6e31b7db2.jpeg'
				ELSE artist_img
			END
			WHERE event_id IN (1, 2, 3, 4, 5, 6)"
		);
	}

	public function down(): void
	{
		$this->execute(
			"UPDATE events
			SET artist_img = NULL
			WHERE (event_id = 1 AND artist_img = 'aedcc88dfad3404a84ae66629db05f1d.jpg')
				OR (event_id = 2 AND artist_img = 'f2cf143b46c4a16241df184908c61007.jpeg')
				OR (event_id = 3 AND artist_img = '5e791412039465777ee38fe69493ab84.jpeg')
				OR (event_id = 4 AND artist_img = '16b3025e219b66ee59dd69ce83b0cf4a.png')
				OR (event_id = 5 AND artist_img = '256594c70b232c1804a7ffc8f3afce59.jpeg')
				OR (event_id = 6 AND artist_img = '96d1b2f890f8f0992554f1b6e31b7db2.jpeg')"
		);
	}
}
