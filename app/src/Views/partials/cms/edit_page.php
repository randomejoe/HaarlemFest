<div class="cms-item-container column">
    <?php 
    $remove = true;
    $itemType = "content";
    $page = $item;
    foreach ($page->getContent() as $item)
    {
        require __DIR__ . '/cms_item.php';      
    }
    require __DIR__ . '/add_page_content.php';
    ?>
</div>