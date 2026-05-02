<?php
    require __DIR__ . '/../Views/partials/header.php';
    ?><div class='dynamic-page-content-container'><?php
    foreach ($page->getContent() as $pageContentItem) {
        if ($pageContentItem->getPageData() != null) {
            $data = $pageContentItem->getPageData();
        } else {
            $data = null;
        }

        if (!empty($pageContentItem->getName())) {
            ?><div><?php require __DIR__ . '/../Views/partials/page_components/' . $pageContentItem->getName() . '.php'; ?></div><?php
        }
    }
    
    ?></div><?php
    require __DIR__ . '/../Views/partials/footer.php';
?>