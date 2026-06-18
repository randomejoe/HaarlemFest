<?php

use Phinx\Migration\AbstractMigration;

class SeedJazzPageContent extends AbstractMigration
{
	public function up(): void
	{
		$rows = [
			[
				'page_id' => 5,
				'component_name' => 'hero_banner',
				'data' => json_encode([
					'date_badge' => '30th July to 2nd August',
					'heading' => 'Haarlem Jazz 2026',
					'subheading' => 'Intimate Club Nights At Patronaat & Free Open-Air Finale On Grote Markt',
					'primary_cta_label' => "Ticket's info",
					'primary_cta_url' => '#tickets',
					'secondary_cta_label' => 'View lineup',
					'secondary_cta_url' => '#lineup',
					'scroll_target' => '#heros',
					'background_image' => 'fb49c801dfc9109264ab8ec4baf0760d.jpeg',
				]),
			],
			[
				'page_id' => 5,
				'component_name' => 'split_content_block',
				'data' => json_encode([
					'heading' => 'Haarlem Jazz - Back and better than ever',
					'body_text' => "For one exciting weekend, The Festival brings the soul of Haarlem Jazz roaring back to life. We've invited some of the city's favorite bands and artists who've lit up the Patronaat stage in previous years to return for three intimate club nights and a free Sunday finale at Grote Markt.",
					'image_alignment' => 'left',
					'image' => '702dbdf5ea601dbdd3502aaa8410d85c.png',
				]),
			],
			[
				'page_id' => 5,
				'component_name' => 'split_content_block',
				'data' => json_encode([
					'heading' => 'Three nights at Patronaat, one huge free Sunday',
					'body_text' => "Thursday, Friday and Saturday: three different halls inside the legendary Patronaat will swing with everything from New Orleans brass to modern soul-jazz and everything in between. Sunday: the party moves outdoors. Six of the weekend's hottest acts perform completely free on the main stage at Grote Markt. No ticket needed, just bring your good mood.",
					'image_alignment' => '',
					'image' => '75a4c34eafbea4f9e42593443b658dff.png',
				]),
			],
			[
				'page_id' => 5,
				'component_name' => 'split_content_block',
				'data' => json_encode([
					'heading' => 'Grab your spot or go all-in',
					'body_text' => "Single tickets start at just \u{20AC}10, or upgrade to a Day Pass (\u{20AC}35) or the ultimate 3-Day All-Access Pass (\u{20AC}80) and move freely between all halls on Thursday to Saturday. Sunday is on us. Everyone is invited.",
					'image_alignment' => 'left',
					'image' => '168dd8ff67409edb34e2149cf8e15c54.png',
				]),
			],
			[
				'page_id' => 5,
				'component_name' => 'lineup_section',
				'data' => null,
			],
			[
				'page_id' => 5,
				'component_name' => 'tickets_passes',
				'data' => json_encode([
					'section_id' => 'tickets',
					'heading' => 'Tickets & Passes',
					'intro_text' => 'Choose the pass that fits your festival experience, from single-night tickets to full weekend access.',
					'card_1_title' => 'Single Night',
					'card_1_price' => "\u{20AC}10",
					'card_1_description' => 'Entry to one evening at Patronaat. Pick your favorite night and enjoy the intimate club atmosphere.',
					'card_1_cta_label' => 'Get Pass',
					'card_1_cta_url' => '#pass',
					'card_1_badge' => '',
					'card_2_title' => 'Day Pass',
					'card_2_price' => "\u{20AC}35",
					'card_2_description' => 'Access to all Haarlem Jazz events for one full day. Move freely between all three halls at Patronaat.',
					'card_2_cta_label' => 'Get Pass',
					'card_2_cta_url' => '#daypass',
					'card_2_badge' => '',
					'card_3_title' => '3-Day Pass',
					'card_3_price' => "\u{20AC}80",
					'card_3_description' => 'Full access Thursday through Saturday. Experience every artist, every hall, every moment of Haarlem Jazz 2026.',
					'card_3_cta_label' => 'Get Pass',
					'card_3_cta_url' => '#3daypass',
					'card_3_badge' => 'Best Value',
					'note_1' => 'Sunday finale at Grote Markt is completely free for everyone',
					'note_2' => 'Limited seating at Patronaat. Early booking recommended',
				]),
			],
			[
				'page_id' => 5,
				'component_name' => 'venues_map',
				'data' => json_encode([
					'section_id' => '',
					'heading' => 'Venues & Map',
					'intro_text' => 'Explore Haarlem Jazz venues. Club nights are at Patronaat, and the finale is on Grote Markt.',
					'map_image_alt' => 'map of haarlem',
					'location_1_name' => 'Patronaat',
					'location_1_address' => 'Zijlsingel 2, 2013 DN Haarlem',
					'location_1_description' => 'Indoor performances occur in three halls. Main Hall hosts headline acts, while Second and Third Halls feature simultaneous shows for All-Access Pass holders.',
					'location_2_name' => 'Grote Markt',
					'location_2_address' => '2011 DR Haarlem',
					'location_2_description' => "Location of Sunday's free open-air finale. The historic main square of Haarlem transforms into an outdoor concert venue for the festival's closing celebration.",
					'map_image' => '1369a324ad4ebfe8959d4010897695cb.png',
				]),
			],
		];

		$table = $this->table('page_content');
		$table->insert($rows)->saveData();
	}

	public function down(): void
	{
		$this->execute(
			"DELETE FROM page_content WHERE page_id = 5 AND component_name IN ('hero_banner', 'split_content_block', 'lineup_section', 'tickets_passes', 'venues_map')"
		);
	}
}
