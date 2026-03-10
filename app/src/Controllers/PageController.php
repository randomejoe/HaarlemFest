<?php

namespace App\Controllers;

use App\Services\PageService;

class PageController
{
    private PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function showPage($page): void
    {
        $page = $this->pageService->getPage($page);

        foreach ($page as $pageContentItem) {
            if (isset($pageContentItem['data'])) {
                $data = json_decode($pageContentItem['data'], true);
            }
            else {
                $data = null;
            }
            require __DIR__ . '/../Views/partials/page_components/' . $pageContentItem['component_name'] . '.php';
        }
    }
}
