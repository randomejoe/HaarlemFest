<?php

namespace App\Controllers;

use App\Services\PageService;

class CmsController
{
    private PageService $pageService;

    public function __construct()
    {
        $this->pageService = new PageService();
    }

    public function showCmsDashboard(): void
    {
        require(__DIR__ . '/../Views/cms/index.php');
    }

    public function showCmsPages(): void
    {
        $pages = $this->pageService->getAllPages();
        extract(['pages' => $pages], EXTR_SKIP);
        require(__DIR__ . '/../Views/cms/pages.php');
    }

    public function createPage(): void
    {
        $title = $_POST['title'];
        $success = $this->pageService->createPage($title);

        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $title;
            header('Location: /cms/pages');
        }
    }

    public function showCmsComponents(): void
    {
        require(__DIR__ . '/../Views/cms/components.php');
    }

    public function showCmsTickets(): void
    {
        require(__DIR__ . '/../Views/cms/tickets.php');
    }

    public function showCmsUsers(): void
    {
        require(__DIR__ . '/../Views/cms/users.php');
    }

    public function showCmsEvents(): void
    {
        require(__DIR__ . '/../Views/cms/events.php');
    }
}
