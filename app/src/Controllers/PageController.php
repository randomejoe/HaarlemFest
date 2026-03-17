<?php

namespace App\Controllers;

use App\Services\PageService;
use App\Services\EventService;

class PageController
{
    private PageService $pageService;
    private EventService $eventService;

    public function __construct(PageService $pageService, EventService $eventService)
    {
        $this->pageService = $pageService;
        $this->eventService = $eventService;
    }

    public function showPage($page): void
    {
        $page = $this->pageService->getPage($page);
        $eventService = $this->eventService;
        echo "<link rel='stylesheet' href='/festival.css'>";
        require __DIR__ . '/../Views/partials/header.php';
        ?><div class='dynamic-page-content-container'><?php
        if (isset($page['page_id'])) {
            $pageContentItem = $page;
            if (isset($pageContentItem['data'])) {
                $data = json_decode($pageContentItem['data'], true);
            }
            else {
                $data = null;
            }
            require __DIR__ . '/../Views/partials/page_components/' . $pageContentItem['component_name'] . '.php';
        }
        else {
            foreach ($page as $pageContentItem) {
            if (isset($pageContentItem['data'])) {
                $data = json_decode($pageContentItem['data'], true);
            }
            else {
                $data = null;
            }
            require __DIR__ . '/../Views/partials/page_components/' . $pageContentItem['component_name'] . '.php';
        }
        }?></div><?php
        require __DIR__ . '/../Views/partials/footer.php';        
    }
}
