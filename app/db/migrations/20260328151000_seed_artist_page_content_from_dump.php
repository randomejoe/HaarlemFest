<?php

use Phinx\Migration\AbstractMigration;

class SeedArtistPageContentFromDump extends AbstractMigration
{
	public function up(): void
	{
		// Remove existing rows for the same page/component pairs to keep the seed deterministic.
		$this->execute(
			"DELETE FROM page_content
			 WHERE (page_id = 48 AND component_name IN ('artist_hero', 'artist_story', 'artist_gallery', 'artist_listening', 'artist_schedule', 'artist_venues', 'jazz_program'))
			    OR (page_id = 49 AND component_name IN ('artist_hero', 'artist_story', 'artist_gallery', 'artist_listening', 'artist_schedule', 'artist_venues', 'jazz_program'))
			    OR (page_id = 5 AND component_name = 'jazz_program')"
		);

		$rows = [
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'artist_hero',
				'data' => '{"artist_name":"Ntjam Rosie","artist_summary":"Afro-soul jazz with Dutch warmth and Cameroonian roots","artist_location":"Amsterdam, Netherlands","artist_genres":"Afro-soul, Jazz","featured_event_id":"3","featured_event_note":"Het Patronaat \\u00b7 Main Hall","tickets_cta_label":"Tickets & Schedule","tickets_cta_url":"#schedule","artist_image_alt":"Ntjam Rosie","artist_image":"9163f37d12bddaa073e78ea8a249bff1.jpeg"}',
			],
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'artist_story',
				'data' => '{"section_id":"","story_title":"About Ntjam Rosie","paragraph_1":"Ntjam Rosie is a Dutch-Cameroonian jazz vocalist and songwriter who effortlessly blends soul, African rhythms, and contemporary jazz into a sound that\'s uniquely her own. Born in Cameroon and raised in the Netherlands, she carries both worlds in her voice, warm, powerful, and deeply expressive.","paragraph_2":"Her music draws from a rich palette of influences, from traditional African harmonies to American soul legends like Stevie Wonder and contemporary jazz innovators. With her powerful voice and heartfelt compositions, Ntjam has become one of Europe\'s most celebrated jazz vocalists.","paragraph_3":"Back in Haarlem after a memorable performance in 2019, Ntjam Rosie returns to The Festival with new material and the kind of stage presence that turns a concert into an unforgettable experience.","quote_text":"Music is my way of connecting cultures, hearts, and stories. Every performance is a celebration.","quote_author":"Ntjam Rosie","highlights_title":"Career Highlights","highlight_1_title":"Edison Award 2010","highlight_1_text":"Best Jazz Vocal Album for \'Elle\'","highlight_2_title":"North Sea Jazz Festival","highlight_2_text":"Featured performer 2009, 2012, 2015","highlight_3_title":"Montreux Jazz Festival","highlight_3_text":"Performed at the prestigious Swiss festival in 2011","highlight_4_title":"International Collaborations","highlight_4_text":"Worked with Gregory Porter, Snarky Puppy, and Metropole Orkest","highlight_5_title":"Debut Album \'Vitamin\'","highlight_5_text":"Released 2007 to critical acclaim across Europe","highlight_6_title":"European Tour 2026","highlight_6_text":"Touring 15 countries including special Haarlem show"}',
			],
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'artist_gallery',
				'data' => '{"section_id":"","card_1_image_alt":"Ntjam Rosie","card_1_caption":"Ntjam Rosie","card_2_image_alt":"Ntjam Rosie","card_2_caption":"Ntjam Rosie","card_1_image":"fae9ea670880c7caf9ad52a10b0897b7.jpeg","card_2_image":"3e24dbd92391e82c05d6efdf4b888cc3.jpeg"}',
			],
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'artist_listening',
				'data' => '{"section_id":"","section_title":"Essential Listening","card_1_image_alt":"elle","card_1_preview":"1","card_1_badge":"Edison Award Winner","card_1_tracks_label":"12 tracks","card_1_year_label":"2011","card_1_title":"Elle","card_1_description":"Edison Award-winning album featuring soulful jazz interpretations and original compositions.","card_1_featured":"0","card_2_image_alt":"home cooking","card_2_preview":"0","card_2_badge":"Debut Album","card_2_tracks_label":"10 tracks","card_2_year_label":"2022","card_2_title":"Home cooking","card_2_description":"A groove-driven album where soul, jazz, and Afro rhythms are blend into heartfelt songs.","card_2_featured":"0","card_3_image_alt":"Family & Friends","card_3_preview":"1","card_3_badge":"Latest Release","card_3_tracks_label":"9 tracks","card_3_year_label":"2020","card_3_title":"Family & Friends","card_3_description":"An uplifting album, Ntjam Rosie combines soulful vocals and grooves to honor those who shape our lives.","card_3_featured":"1","card_4_image_alt":"Breaking Cycles","card_4_preview":"0","card_4_badge":"Fan Favorite","card_4_tracks_label":"11 tracks","card_4_year_label":"2017","card_4_title":"Breaking Cycles","card_4_description":"A vibrant album where growth and resilience are explored.","card_4_featured":"0","id":"59","card_1_image":"8ae2ac6d506fa41b668e9c3288f2f7dd.jpeg","card_2_image":"fbd319e6a2e936bcc77fad74cfed1413.jpeg","card_3_image":"1aac55710cae0593ebcc7079ff976dfd.jpeg","card_4_image":"255f7362f32fd53e6635f1add769963c.jpeg"}',
			],
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'artist_schedule',
				'data' => '{"section_id":"#schedule","tickets_cta_url":"#jazz-open"}',
			],
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'artist_venues',
				'data' => '{"section_id":"","venues_title":"Venue Information","venues_subtitle":"Where you\'ll see Ntjam Rosie live","map_title":"Festival Locations","map_image_alt":"map","map_image":"8b81deabe0bf81717394fdb901e0cbfd.png"}',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'artist_hero',
				'data' => '{"artist_name":"Jonna Frazer","artist_summary":"Dutch hip-hop storyteller with a soulful, jazz-infused groove","artist_location":"Zaandam, Netherlands","artist_genres":"Hip-hop, R&B, Jazz crossover","featured_event_id":"6","featured_event_note":"","tickets_cta_label":"Tickets & Schedule","tickets_cta_url":"#tickets","artist_image_alt":"Jonna Frazer","artist_image":"a44005da08107066fdcbe549084adebf.png"}',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'artist_story',
				'data' => '{"section_id":"","story_title":"About Jonna Fraser","paragraph_1":"Jonna Fraser (Jonathan Jeffrey Grando) is a Dutch-Surinamese rapper and singer whose smooth vocals and melodic flow have made him one of the Netherlands\' most popular urban artists. Born in Rotterdam and raised in Zaandam, he blends hip-hop, R&B and soulful pop into songs that feel both intimate and made for big festival stages.","paragraph_2":"He discovered rap at the age of eleven and never let go of the mic. After his breakthrough with the New Wave collective in 2015, Jonna evolved into a genuine all-round performer, collecting gold and platinum records and amassing more than a billion streams with hits like \\"Do or Die\\", \\"Ik Kan Je Niet Laten\\" and \\"Ik Zag Je Staan\\".","paragraph_3":"For Haarlem Jazz he brings a special live band set, stretching his songs into warm, groove-driven arrangements with keys, horns and improvisation. It\'s a show where hip-hop stories, soulful hooks and jazz-coloured rhythms meet, turning the club energy of his records into a late-night festival experience.","quote_text":"On stage I want people to feel like they\'re part of my story dancing, singing, and forgetting the world for a moment.","quote_author":"Jonna Fraser","highlights_title":"Career Highlights","highlight_1_title":"New Wave Breakthrough","highlight_1_text":"Part of the New Wave collective, debut album hit #1 in the Dutch charts.","highlight_2_title":"Multi-Platinum Streaming Star","highlight_2_text":"1.5 billion streams make him a top Dutch urban artist.","highlight_3_title":"Hit Singles & Anthems","highlight_3_text":"Hits like \\"Do or Die\\" and \\"Ik Kan Je Niet Laten\\"","highlight_4_title":"Chart-Topping Albums","highlight_4_text":"Projects like Goed Teken and Lion reached the top of the Dutch Album Top 100.","highlight_5_title":"Blessed For Life Concerts","highlight_5_text":"Headlined two sold-out shows at AFAS Live in 2023 with a full band.","highlight_6_title":"Ambassador of Freedom","highlight_6_text":"Became an \\"Ambassador of Freedom\\" in 2021, performing at Dutch Liberation Day events."}',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'artist_gallery',
				'data' => '{"section_id":"","card_1_image_alt":"Jonna Frazer","card_1_caption":"Jonna Frazer","card_2_image_alt":"Jonna Frazer","card_2_caption":"Jonna Frazer","id":"64","card_1_image":"4be169811be367f96a1acf39c5a39959.jpeg","card_2_image":"bc852faac7cd305e71377ad289f26da3.jpeg"}',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'artist_listening',
				'data' => '{"section_id":"","section_title":"Essential Listening","card_1_image_alt":"Jonna Frazer","card_1_preview":"1","card_1_badge":"Edison Award Winner","card_1_tracks_label":"12 tracks","card_1_year_label":"2011","card_1_title":"Blessed","card_1_description":"A project where Jonna\'s vocals, lyrics and rap blend in mid-tempo grooves.","card_1_featured":"1","card_2_image_alt":"Jonna Frazer","card_2_preview":"0","card_2_badge":"Latest Release","card_2_tracks_label":"10 tracks","card_2_year_label":"2023","card_2_title":"Blessed For Life","card_2_description":"A personal, story-driven record that blends rap and R&B into warm, melodic tracks about growth, love and loyalty.","card_2_featured":"0","card_3_image_alt":"Jonathan","card_3_preview":"1","card_3_badge":"Debut Album","card_3_tracks_label":"9 tracks","card_3_year_label":"2017","card_3_title":"Jonathan","card_3_description":"A story-driven record blending rap and R&B about growth, love, and loyalty.","card_3_featured":"1","card_4_image_alt":"Alle Tijd","card_4_preview":"0","card_4_badge":"Fan Favorite","card_4_tracks_label":"11 tracks","card_4_year_label":"2017","card_4_title":"Alle Tijd","card_4_description":"An energetic release of Jonna\'s catchy melodies and sharp verses.","card_4_featured":"0","id":"65","card_1_image":"bb43fd20f9bd4d6e97aada7ccdb505f4.jpeg","card_2_image":"50936c26cb96a3335a2a4a904261b026.jpeg","card_3_image":"8097ebceff70205cfdacfe549adcd734.jpeg","card_4_image":"12b1b19d6a35709f43fdad6d3e9dfe88.jpeg"}',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'artist_schedule',
				'data' => '{"section_id":"tickets","tickets_cta_url":"#jazz-open"}',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'artist_venues',
				'data' => '{"section_id":"","venues_title":"Venue Information","venues_subtitle":"Where you\'ll see Jonna Frazer live","map_title":"Festival Locations","map_image_alt":"map","id":"68","map_image":"a1c5cad5a02d8332c10c8b8c667093eb.png"}',
			],
			[
				'page_id' => 5,
				'sort_order' => 0,
				'component_name' => 'jazz_program',
				'data' => '[]',
			],
			[
				'page_id' => 48,
				'sort_order' => 0,
				'component_name' => 'jazz_program',
				'data' => '[]',
			],
			[
				'page_id' => 49,
				'sort_order' => 0,
				'component_name' => 'jazz_program',
				'data' => '[]',
			],
		];

		$this->table('page_content')->insert($rows)->saveData();
	}

	public function down(): void
	{
		$this->execute(
			"DELETE FROM page_content
			 WHERE (page_id = 48 AND component_name IN ('artist_hero', 'artist_story', 'artist_gallery', 'artist_listening', 'artist_schedule', 'artist_venues', 'jazz_program'))
			    OR (page_id = 49 AND component_name IN ('artist_hero', 'artist_story', 'artist_gallery', 'artist_listening', 'artist_schedule', 'artist_venues', 'jazz_program'))
			    OR (page_id = 5 AND component_name = 'jazz_program')"
		);
	}
}
