<?php

namespace App\Controllers;

use App\Services\PageService;
use App\Services\PageRenderer;

class PageController
{
    private PageRenderer $renderer;
    private PageService $pageService;

    public function __construct(PageRenderer $renderer, PageService $pageService)
    {
        $this->renderer = $renderer;
        $this->pageService = $pageService;
    }

    public function showPage($page): void
    {
        $page = $this->pageService->getPage(urldecode($page));
        
        $this->renderer->renderPage($page);
    }
}
