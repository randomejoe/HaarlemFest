<?php
    $options = [];  
    $param = "event";
    foreach ($categories as $category) {
        $options[$category] = $category;
    } 
    $baseRoute = '/cms/events';
    $hasNone = true;
    
    if (isset($params['event'])) {
        $currentOption = $params['event'];
    }
    else {
        $currentOption = "None";
    }
    
    require __DIR__ . '/selector.php';
?>


<script src="/js/cms_selector.js"></script>