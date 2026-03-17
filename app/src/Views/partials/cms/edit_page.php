<div class="cms-item-container column">

    <?php 
    $remove = true;
    $itemType = "content";
    foreach ($item->getContent() as $contentItem)
    {
        if (isset($contentItem['name'])) {
            $itemName = $contentItem['name'];
        $itemId = $contentItem['id'];
        require __DIR__ . '/cms_item.php';
        }        
    }
    require __DIR__ . '/add_page_content.php'
    ?>
</div>