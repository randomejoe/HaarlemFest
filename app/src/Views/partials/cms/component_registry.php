<!-- Name is the name of the field, visible when editing values in the cms and what is used to get the value through $data
(ex. 'name'=>'count' is accessed as $data['count'])
type refers to the type of edit field, types are image and text. More can be added on request
element is only for text elements currently and refers to the element used to wrap the text in the wysiwyg editor
-->
<?php $components = [
    'text' => [
        'fields' => [
            ['name' => 'text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'has padding', 'type' => 'checkbox'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ],
    ],
    'image' => [
        'fields' => [
            ['name' => 'imgSource', 'type' => 'image'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'title' => [
        'fields' => [
            ['name' => 'text', 'type' => 'text', 'element' => 'h1'],
            ['name' => 'has_top_padding', 'type' => 'checkbox'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'sub_title' => [
        'fields' => [
            ['name' => 'text', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'paragraph' => [
        'fields' => [
            ['name' => 'header_text', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'paragraph_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'history_detailpage_top' => [
        'fields' => [
            ['name' => 'title_text', 'type' => 'text', 'element' => 'h1'],
            ['name' => 'header_text', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'paragraph_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'map_image_source', 'type' => 'image'],
            ['name' => 'night_image_source', 'type' => 'image'],
            ['name' => 'night_image_caption', 'type' => 'text', 'element' => 'p'],
            ['name' => 'has_top_padding', 'type' => 'checkbox'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'double_caption_image' => [
        'fields' => [
            ['name' => 'left_image_source', 'type' => 'image'],
            ['name' => 'left_image_caption', 'type' => 'text', 'element' => 'p'],
            ['name' => 'right_image_source', 'type' => 'image'],
            ['name' => 'right_image_caption', 'type' => 'text', 'element' => 'p'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'big_ticket_button' => [
        'fields' => [
            ['name' => 'button_link', 'type' => 'text', 'element' => 'p'],
            ['name' => 'text', 'type' => 'text', 'element' => 'h2'],
        ]
    ],
    'history_schedule' => [
        'fields' => [
            ['name' => 'header_text', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'buy_ticket_button_link', 'type' => 'text', 'element' => 'p']
        ]
    ],
    'history_locations' => [
        'fields' => [
            ['name' => 'header_text', 'type' => 'text', 'element' => 'h2'],
        ]
    ],
    'full_size_image' => [
        'fields' => [
            ['name' => 'image_source', 'type' => 'image'],
            ['name' => 'has_horizontal_padding', 'type' => 'checkbox'],
        ]
    ],
    'hero_banner' => [
        'fields' => [
            ['name' => 'background_image', 'type' => 'image'],
            ['name' => 'date_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'heading', 'type' => 'text', 'element' => 'h1'],
            ['name' => 'subheading', 'type' => 'text', 'element' => 'p'],
            ['name' => 'primary_cta_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'primary_cta_url', 'type' => 'text', 'element' => 'p'],
            ['name' => 'secondary_cta_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'secondary_cta_url', 'type' => 'text', 'element' => 'p'],
            ['name' => 'scroll_target', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'split_content_block' => [
        'fields' => [
            ['name' => 'heading', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'body_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'image', 'type' => 'image'],
            ['name' => 'image_alignment', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'artist_hero' => [
        'fields' => [
            ['name' => 'artist_name', 'type' => 'text', 'element' => 'h1'],
            ['name' => 'artist_summary', 'type' => 'text', 'element' => 'p'],
            ['name' => 'artist_location', 'type' => 'text', 'element' => 'p'],
            ['name' => 'artist_genres', 'type' => 'text', 'element' => 'p'],
            ['name' => 'featured_event_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'featured_event_note', 'type' => 'text', 'element' => 'p'],
            ['name' => 'tickets_cta_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'tickets_cta_url', 'type' => 'text', 'element' => 'p'],
            ['name' => 'artist_image', 'type' => 'image'],
            ['name' => 'artist_image_alt', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'artist_story' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'story_title', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'paragraph_1', 'type' => 'text', 'element' => 'p'],
            ['name' => 'paragraph_2', 'type' => 'text', 'element' => 'p'],
            ['name' => 'paragraph_3', 'type' => 'text', 'element' => 'p'],
            ['name' => 'quote_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'quote_author', 'type' => 'text', 'element' => 'p'],
            ['name' => 'highlights_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'highlight_1_title', 'type' => 'text', 'element' => 'h4'],
            ['name' => 'highlight_1_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'highlight_2_title', 'type' => 'text', 'element' => 'h4'],
            ['name' => 'highlight_2_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'highlight_3_title', 'type' => 'text', 'element' => 'h4'],
            ['name' => 'highlight_3_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'highlight_4_title', 'type' => 'text', 'element' => 'h4'],
            ['name' => 'highlight_4_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'highlight_5_title', 'type' => 'text', 'element' => 'h4'],
            ['name' => 'highlight_5_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'highlight_6_title', 'type' => 'text', 'element' => 'h4'],
            ['name' => 'highlight_6_text', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'artist_gallery' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_image', 'type' => 'image'],
            ['name' => 'card_1_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_caption', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_image', 'type' => 'image'],
            ['name' => 'card_2_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_caption', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'artist_schedule' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'tickets_cta_url', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'artist_listening' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'section_title', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'card_1_image', 'type' => 'image'],
            ['name' => 'card_1_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_preview', 'type' => 'checkbox'],
            ['name' => 'card_1_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_tracks_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_year_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_1_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_featured', 'type' => 'checkbox'],
            ['name' => 'card_2_image', 'type' => 'image'],
            ['name' => 'card_2_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_preview', 'type' => 'checkbox'],
            ['name' => 'card_2_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_tracks_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_year_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_2_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_featured', 'type' => 'checkbox'],
            ['name' => 'card_3_image', 'type' => 'image'],
            ['name' => 'card_3_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_preview', 'type' => 'checkbox'],
            ['name' => 'card_3_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_tracks_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_year_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_3_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_featured', 'type' => 'checkbox'],
            ['name' => 'card_4_image', 'type' => 'image'],
            ['name' => 'card_4_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_4_preview', 'type' => 'checkbox'],
            ['name' => 'card_4_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_4_tracks_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_4_year_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_4_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_4_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_4_featured', 'type' => 'checkbox'],
        ]
    ],
    'artist_venues' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'venues_title', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'venues_subtitle', 'type' => 'text', 'element' => 'p'],
            ['name' => 'map_title', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'map_image', 'type' => 'image'],
            ['name' => 'map_image_alt', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'lineup_section' => [
        'fields' => []
    ],
    'jazz_program' => [
        'fields' => []
    ],
    'tickets_passes' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'heading', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'intro_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_1_price', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_cta_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_cta_url', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_1_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_2_price', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_cta_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_cta_url', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_2_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_title', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'card_3_price', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_cta_label', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_cta_url', 'type' => 'text', 'element' => 'p'],
            ['name' => 'card_3_badge', 'type' => 'text', 'element' => 'p'],
            ['name' => 'note_1', 'type' => 'text', 'element' => 'p'],
            ['name' => 'note_2', 'type' => 'text', 'element' => 'p'],
        ]
    ],
    'venues_map' => [
        'fields' => [
            ['name' => 'section_id', 'type' => 'text', 'element' => 'p'],
            ['name' => 'heading', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'intro_text', 'type' => 'text', 'element' => 'p'],
            ['name' => 'map_image', 'type' => 'image'],
            ['name' => 'map_image_alt', 'type' => 'text', 'element' => 'p'],
            ['name' => 'location_1_name', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'location_1_address', 'type' => 'text', 'element' => 'p'],
            ['name' => 'location_1_description', 'type' => 'text', 'element' => 'p'],
            ['name' => 'location_2_name', 'type' => 'text', 'element' => 'h3'],
            ['name' => 'location_2_address', 'type' => 'text', 'element' => 'p'],
            ['name' => 'location_2_description', 'type' => 'text', 'element' => 'p'],
        ]
    ]
]
?>