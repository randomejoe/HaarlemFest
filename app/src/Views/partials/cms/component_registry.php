<!-- Name is the name of the field, visible when editing values in the cms and what is used to get the value through $data
(ex. 'name'=>'count' is accessed as $data['count'])
type refers to the type of edit field, types are image and text. More can be added on request
element is only for text elements currently and refers to the element used to wrap the text in the wysiwyg editor
-->
<?php $components = [
    'text' => [
        'fields' => [
            ['name' => 'text', 'type' => 'text', 'element' => 'p'],
        ],
    ],
    'image' => [
        'fields' => [
            ['name' => 'imgSource', 'type' => 'image'],
        ]
    ],
    'title' => [
        'fields' => [
            ['name' => 'text', 'type' => 'text', 'element' => 'h1']
        ]
    ],
    'paragraph' => [
        'fields' => [
            ['name' => 'header_text', 'type' => 'text', 'element' => 'h2'],
            ['name' => 'paragraph_text', 'type' => 'text', 'element' => 'p']
        ]
    ],
    'history_schedule' => [
        'fields' => [
            ['name' => 'header_text', 'type' => 'text', 'element' => 'h2'],
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
    'lineup_section' => [
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