<div class="cms-item-container">

    <?php 
    $remove = true;
    $itemType = "content";
    foreach ($item as $contentItem)
    {
        $itemName = $contentItem['component_name'];
        $itemId = $contentItem['content_id'];
        require __DIR__ . '/cms_item.php';
    }
    ?>
</div>