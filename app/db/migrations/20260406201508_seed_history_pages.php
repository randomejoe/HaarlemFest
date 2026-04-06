<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedHistoryPages extends AbstractMigration
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
        // Seed pages table
        $pages = $this->table('pages');
        $pages->insert([
            ['page_id' => 1, 'title' => 'A stroll through history', 'is_main_event' => 1],
            ['page_id' => 61, 'title' => 'History tickets', 'is_main_event' => 0],
            ['page_id' => 62, 'title' => 'Church of St. Bavo', 'is_main_event' => 0],
            ['page_id' => 63, 'title' => 'Amsterdamse poort', 'is_main_event' => 0],
        ])->saveData();

        // Seed page_content table
        $pageContent = $this->table('page_content');
        $pageContent->insert([
            ['page_id' => 1, 'component_name' => 'title', 'data' => '{"text":"A stroll through History","has_top_padding":true}', 'sort_order' => 0],
            ['page_id' => 1, 'component_name' => 'paragraph', 'data' => '{"header_text":"General information",
            "paragraph_text":"A Stroll through History is a <strong>2.5 hour long walking event</strong> where you discover and learn about important landmarks of Haarlems history, from churches to courts, <strong>together with 11 others</strong>. <br>As this stroll is of an historic nature, participants have to be of <strong>ages 12 or up</strong> and <strong>no strollers are allowed</strong> during the stroll. The stroll will visit <strong>9 historical landmarks</strong> and a has a <strong>break after the 5th location, the Jopenkerk</strong>. During the break there will be <strong>1 free drink per participant</strong>."}', 'sort_order' => 0],
            ['page_id' => 1, 'component_name' => 'paragraph', 'data' => '{"header_text":"Pricing","paragraph_text":"Tickets for a Stroll through History are sold as personal ticket or as a family ticket. <br>• Regular Participant: € 17,50  <br>• Family ticket (per 4 participants): € 60,-"}', 'sort_order' => 0],
            ['page_id' => 1, 'component_name' => 'history_schedule', 'data' => '{"header_text":"Schedule","buy_ticket_button_link":"/history_tickets"}', 'sort_order' => 0],
            ['page_id' => 1, 'component_name' => 'full_size_image', 'data' => '{"has_horizontal_padding":"1","image_source":"98f6b1330fdb86bc06c0eb4ab815dc3a.png"}', 'sort_order' => 0],
            ['page_id' => 1, 'component_name' => 'history_locations', 'data' => '{"header_text":"Locations that are visited"}', 'sort_order' => 0],

            ['page_id' => 61, 'component_name' => 'title', 'data' => '{"text":"A stroll through History","has_top_padding":true, "has_horizontal_padding":true}', 'sort_order' => 0],
            ['page_id' => 61, 'component_name' => 'sub_title', 'data' => '{"text":"Get tickets","has_horizontal_padding":true}', 'sort_order' => 0],
            ['page_id' => 61, 'component_name' => 'history_ticketing', 'data' => '{"single_ticket_price":"17.50","family_ticket_price":"60.00", "ticket_image":"9808339aa376af8070ebfbe3bf5726f3.png"}', 'sort_order' => 0],

            ['page_id' => 62, 'component_name' => 'history_detailpage_top', 'data' => '{"title_text":"A Stroll through History","header_text":"Church of St. Bavo", "paragraph_text":"The church of St.Bavo is one of many churches that have been in its location. Its predecessors have been build both from wooden and stone materials.<br><br>Its most recent predecessor was destroyed by a fire in 1370 and was then rebuild over 150 years, being finished in 1520, being the church we know today.<br><br>The dutch version of the saying “the die is cast” might originate from an event in 1573 where a cannonball was aimed at the church. While it missed its target, it did end up staying in the church where it can still be seen in the wall today. <br><br>There have also been some notable figures that have been buried in the church of St.Bavo.<br>Some of these notable figures are:<br>Jan Adriaanszoon Leeghwater (1575 - 1650), A Dutch windmill builder.<br>Frans Hals (ca. 1583-1666), A famous Dutch painter.<br>Pieter Teyler van der Hulst (1702-1778), founder of Teyler’s museum.", "map_image_source":"72a8255c6b3f7988b41e6080c692a630.png", "night_image_source":"ebe50b7a40884a2e2f61f8028528af24.png", "night_image_caption":"Church of St.Bavo at night", "has_top_padding":"1", "has_horizontal_padding":"1"}', 'sort_order' => 0],
            ['page_id' => 62, 'component_name' => 'double_caption_image', 'data' => '{"left_image_caption":"Painting of the church of St.Bavo in 1696 made by the painter Gerrit Berckheyde.", "left_image_source":"4005b93ac9c9561adc42a20ead81d23d.png", "right_image_caption":"The cannonball that missed its target in 1573.", "right_image_source":"63a202b0219fda93c752a077bf7bf815.png"}', 'sort_order' => 0],
            ['page_id' => 62, 'component_name' => 'big_ticket_button', 'data' => '{"button_link":"/history_tickets","text":"Get tickets"}', 'sort_order' => 0],

            ['page_id' => 63, 'component_name' => 'history_detailpage_top', 'data' => '{"title_text":"A Stroll through History","header_text":"Amsterdamse poort", "paragraph_text":"The Amsterdamse poort is a gate that was positioned at the end of the old route from Amsterdam to Haarlem, thus why it is called the Amsterdamse poort. <br><br>The Amsterdamse poort is the only gate that survived the Spanish attack during the eighty years’ war without taking any notable damage. It is the last city gate that Haarlem has left, as the others have all been taken down.", "night_image_caption":"Amsterdamse poort at night", "has_top_padding":"1", "has_horizontal_padding":"1"}', 'sort_order' => 0],
            ['page_id' => 63, 'component_name' => 'double_caption_image', 'data' => '{"left_image_caption":"Old drawing of the Amsterdamse poort", "left_image_source":"0f560470b1f50fc73d946b99361f1909.png", "right_image_caption":"Amsterdamse poort during the day", "right_image_source":"a86cdce6fedfce355c7df6e2d3affc53.png"}', 'sort_order' => 0],
            ['page_id' => 63, 'component_name' => 'big_ticket_button', 'data' => '{"button_link":"/history_tickets","text":"Get tickets"}', 'sort_order' => 0],
        ])->saveData();
    }

    public function down(): void
    {
        // Remove content first (because of FK)
        $this->execute("
            DELETE FROM page_content
            WHERE page_id IN (1, 61, 62, 63)
        ");

        // Then remove pages
        $this->execute("
            DELETE FROM pages
            WHERE page_id IN (1, 61, 62, 63)
        ");
    }
}
