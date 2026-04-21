<?php

namespace App\Controllers;

use App\Services\PageService;
use App\Services\EventService;
use App\Services\LocationService;
use App\Services\PlannerService;

class PageController
{
    private PageService $pageService;
    private EventService $eventService;
    private LocationService $locationService;
    private PlannerService $plannerService;

    public function __construct(PageService $pageService, EventService $eventService, LocationService $locationService, PlannerService $plannerService)
    {
        $this->pageService = $pageService;
        $this->eventService = $eventService;
        $this->locationService = $locationService;
        $this->plannerService = $plannerService;
    }

    public function showPage($page): void
    {
        $page = $this->pageService->getPage(urldecode($page));

        $eventService = $this->eventService;
        $locationService = $this->locationService;
        $plannerService = $this->plannerService;

        require __DIR__ . '/../Views/dynamicPage.php';
    }
}
