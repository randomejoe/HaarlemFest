<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedHistoryEvents extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $events = $this->table('events');

        $rows = [
            // Thursday 30 July 2026
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-30 10:00:00', 'end_time' => '2026-07-30 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-30 10:00:00', 'end_time' => '2026-07-30 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-30 13:00:00', 'end_time' => '2026-07-30 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-30 13:00:00', 'end_time' => '2026-07-30 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-30 16:00:00', 'end_time' => '2026-07-30 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-30 16:00:00', 'end_time' => '2026-07-30 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            // Friday 31 July 2026
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 10:00:00', 'end_time' => '2026-07-31 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 10:00:00', 'end_time' => '2026-07-31 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 13:00:00', 'end_time' => '2026-07-31 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 13:00:00', 'end_time' => '2026-07-31 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Chinese history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 13:00:00', 'end_time' => '2026-07-31 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Chinese', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 16:00:00', 'end_time' => '2026-07-31 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-07-31 16:00:00', 'end_time' => '2026-07-31 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            // Saturday 1 August 2026
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 10:00:00', 'end_time' => '2026-08-01 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 10:00:00', 'end_time' => '2026-08-01 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 10:00:00', 'end_time' => '2026-08-01 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 10:00:00', 'end_time' => '2026-08-01 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 13:00:00', 'end_time' => '2026-08-01 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 13:00:00', 'end_time' => '2026-08-01 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 13:00:00', 'end_time' => '2026-08-01 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 13:00:00', 'end_time' => '2026-08-01 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Chinese history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 13:00:00', 'end_time' => '2026-08-01 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Chinese', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 16:00:00', 'end_time' => '2026-08-01 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 16:00:00', 'end_time' => '2026-08-01 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Chinese history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-01 16:00:00', 'end_time' => '2026-08-01 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Chinese', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            // Sunday 2 August 2026
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 10:00:00', 'end_time' => '2026-08-02 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 10:00:00', 'end_time' => '2026-08-02 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 10:00:00', 'end_time' => '2026-08-02 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 10:00:00', 'end_time' => '2026-08-02 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Chinese history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 10:00:00', 'end_time' => '2026-08-02 12:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Chinese', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Chinese history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Chinese', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'Chinese history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 13:00:00', 'end_time' => '2026-08-02 15:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Chinese', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],

            ['name' => 'Dutch history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 16:00:00', 'end_time' => '2026-08-02 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'Dutch', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
            ['name' => 'English history tour', 'location' => 'St. Bavo church', 'start_time' => '2026-08-02 16:00:00', 'end_time' => '2026-08-02 18:30:00', 'venue_id' => null, 'ticket_price' => 17.50, 'ticket_amount' => 12, 'language' => 'English', 'description' => null, 'category' => 'A stroll through history', 'artist_img' => null],
        ];

        $events->insert($rows)->saveData();
    }
}
