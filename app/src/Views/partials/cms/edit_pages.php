<div class="cms-item-container column">

    <?php 
    $remove = true;
    $itemType = "content";
    foreach ($item as $contentItem)
    {
        if (isset($contentItem['component_name'])) {
            $itemName = $contentItem['component_name'];
        $itemId = $contentItem['content_id'];
        require __DIR__ . '/cms_item.php';
        }        
    }
    require __DIR__ . '/add_page_content.php'
    ?>
</div>