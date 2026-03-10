<!-- Name is the name of the field, visible when editing values in the cms and what is used to get the value through $data 
(ex. 'name'=>'count' is accessed as $data['count']) 
type refers to the type of edit field, types are image and text. More can be added on request
element is only for text elements currently and refers to the element used to wrap the text in the wysiwyg editor
-->
<?php $components = [
    'text' => [
        'fields' => [
            ['name'=>'text','type'=>'text', 'element'=>'p'],
        ],
    ],
    'image' => [
        'fields' => [
            ['name'=>'imgSource', 'type' => 'image'],
        ]
    ],
    'title' => [
        'fields' => [
            ['name'=>'title_text', 'type' => 'text', 'element' => 'h2']
        ]
    ]
]
?>