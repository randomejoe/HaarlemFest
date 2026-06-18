<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeHistoryMapImage extends AbstractMigration
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
    public function up(): void
    {
        $this->execute("
            UPDATE page_content
            SET data = '{\"has_top_padding\":\"1\", \"has_horizontal_padding\":\"1\",\"image_source\":\"781f144ab4b659a274425297f2e696d0.png\"}'
            WHERE page_id = 1
              AND component_name = 'full_size_image'
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"has_top_padding\":\"1\", \"header_text\":\"Locations that are visited\"}'
            WHERE page_id = 1
              AND component_name = 'history_locations'
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"text\":\"A stroll through History\",\"has_top_padding\":true, \"has_horizontal_padding\":true}'
            WHERE page_id = 1
              AND component_name = 'title'
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"header_text\":\"General information\", \"has_horizontal_padding\":true,
            \"paragraph_text\":\"A Stroll through History is a <strong>2.5 hour long walking event</strong> where you discover and learn about important landmarks of Haarlems history, from churches to courts, <strong>together with 11 others</strong>. <br>As this stroll is of an historic nature, participants have to be of <strong>ages 12 or up</strong> and <strong>no strollers are allowed</strong> during the stroll. The stroll will visit <strong>9 historical landmarks</strong> and a has a <strong>break after the 5th location, the Jopenkerk</strong>. During the break there will be <strong>1 free drink per participant</strong>.\"}'
            WHERE page_id = 1
              AND component_name = 'paragraph'
              AND content_id = (
                SELECT content_id FROM (
                    SELECT content_id
                    FROM page_content
                    WHERE page_id = 1
                    AND component_name = 'paragraph'
                    ORDER BY content_id ASC
                    LIMIT 1
                ) AS sub
            )
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"has_horizontal_padding\":true, \"header_text\":\"Pricing\",\"paragraph_text\":\"Tickets for a Stroll through History are sold as personal ticket or as a family ticket. <br>• Regular Participant: € 17,50  <br>• Family ticket (per 4 participants): € 60,-\"}'
            WHERE page_id = 1
              AND component_name = 'paragraph'
              AND content_id = (
                SELECT content_id FROM (
                    SELECT content_id
                    FROM page_content
                    WHERE page_id = 1
                    AND component_name = 'paragraph'
                    ORDER BY content_id ASC
                    LIMIT 1 OFFSET 1
                ) AS sub
            )
        ");
    }

    public function down(): void
    {
        // revert to old value (put your old JSON here)
        $this->execute("
            UPDATE page_content
            SET data = '{\"has_horizontal_padding\":\"1\",\"image_source\":\"98f6b1330fdb86bc06c0eb4ab815dc3a.png\"}'
            WHERE page_id = 1
              AND component_name = 'full_size_image'
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"header_text\":\"Locations that are visited\"}'
            WHERE page_id = 1
              AND component_name = 'history_locations'
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"text\":\"A stroll through History\",\"has_top_padding\":true}'
            WHERE page_id = 1
              AND component_name = 'title'
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"header_text\":\"General information\",
            \"paragraph_text\":\"A Stroll through History is a <strong>2.5 hour long walking event</strong> where you discover and learn about important landmarks of Haarlems history, from churches to courts, <strong>together with 11 others</strong>. <br>As this stroll is of an historic nature, participants have to be of <strong>ages 12 or up</strong> and <strong>no strollers are allowed</strong> during the stroll. The stroll will visit <strong>9 historical landmarks</strong> and a has a <strong>break after the 5th location, the Jopenkerk</strong>. During the break there will be <strong>1 free drink per participant</strong>.\"}'
            WHERE page_id = 1
              AND component_name = 'paragraph'
              AND content_id = (
                SELECT content_id FROM (
                    SELECT content_id
                    FROM page_content
                    WHERE page_id = 1
                    AND component_name = 'paragraph'
                    ORDER BY content_id ASC
                    LIMIT 1
                ) AS sub
            )
        ");
        $this->execute("
            UPDATE page_content
            SET data = '{\"header_text\":\"Pricing\",\"paragraph_text\":\"Tickets for a Stroll through History are sold as personal ticket or as a family ticket. <br>• Regular Participant: € 17,50  <br>• Family ticket (per 4 participants): € 60,-\"}'
            WHERE page_id = 1
              AND component_name = 'paragraph'
              AND content_id = (
                SELECT content_id FROM (
                    SELECT content_id
                    FROM page_content
                    WHERE page_id = 1
                    AND component_name = 'paragraph'
                    ORDER BY content_id ASC
                    LIMIT 1 OFFSET 1
                ) AS sub
            )
        ");
    }
}
