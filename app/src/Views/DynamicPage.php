<link rel='stylesheet' href='/festival.css'>
<?php
    require __DIR__ . '/../Views/partials/header.php';
    if (count($page) == 0) {
        require __DIR__ . '/../Views/invalid_page.php';
    } else {
        ?><div class='dynamic-page-content-container'><?php
        if (isset($page['page_id'])) {
            $pageContentItem = $page;
            if (isset($pageContentItem['data'])) {
                $data = json_decode($pageContentItem['data'], true);
            } else {
                $data = null;
            }

            if (!empty($pageContentItem['component_name'])) {
                ?><div><?php require __DIR__ . '/../Views/partials/page_components/' . $pageContentItem['component_name'] . '.php'; ?></div><?php
            }
        } else {
            foreach ($page as $pageContentItem) {
                if (isset($pageContentItem['data'])) {
                    $data = json_decode($pageContentItem['data'], true);
                } else {
                    $data = null;
                }
                if (!empty($pageContentItem['component_name'])) {
                    ?><div><?php require __DIR__ . '/../Views/partials/page_components/' . $pageContentItem['component_name'] . '.php'; ?></div><?php
                }
            }
        } ?></div><?php
    }
    require __DIR__ . '/../Views/partials/footer.php';
?>