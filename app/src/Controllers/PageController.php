<?php

namespace App\Controllers;

use App\Services\PageService;
use App\Services\EventService;
use App\Services\LocationService;

class PageController
{
    private PageService $pageService;
    private EventService $eventService;
    private LocationService $locationService;

    public function __construct(PageService $pageService, EventService $eventService, LocationService $locationService)
    {
        $this->pageService = $pageService;
        $this->eventService = $eventService;
        $this->locationService = $locationService;
    }

    public function showPage($page): void
    {
        $page = $this->pageService->getPage(urldecode($page));

        echo "<link rel='stylesheet' href='/festival.css'>";

        // Collect all components on the page to register their assets
        $pageComponents = [];
        if (count($page) > 0 && !isset($page['page_id'])) {
            foreach ($page as $pageContentItem) {
                if (!empty($pageContentItem['component_name'])) {
                    $componentName = $pageContentItem['component_name'];
                    if (!isset($pageComponents[$componentName])) {
                        $pageComponents[$componentName] = true;
                    }
                }
            }
        } elseif (count($page) > 0 && isset($page['page_id'])) {
            if (!empty($page['component_name'])) {
                $pageComponents[$page['component_name']] = true;
            }
        }

        require __DIR__ . '/../Views/partials/header.php';
        if (count($page) == 0) {
            require __DIR__ . '/../Views/invalid_page.php';
        } else {
            $eventService = $this->eventService;
            $locationService = $this->locationService;
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
                                                                                                                                            }
                                                                                                                                        }
