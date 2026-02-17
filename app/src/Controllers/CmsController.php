<?php

namespace App\Controllers;

use App\View;
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
        echo View::render('cms/index');
    }
    public function showCmsPages(): void
    {
        $pages = $this->pageService->getAllPages();

        echo View::render('cms/pages', ['pages' => $pages]);
    }
    public function createPage() {
        $title = $_POST['title'];
        $success = $this->pageService->createPage($title);
        
        if ($success) {
            $_SESSION['flash_message'] = 'Page created successfully!';
            header('Location: /cms/pages');
        }
    }
    public function showCmsComponents(): void
    {
        echo View::render('cms/components');
    }
    public function showCmsTickets(): void
    {
        echo View::render('cms/tickets');
    }
    public function showCmsUsers(): void
    {
        echo View::render('cms/users');
    }
    public function showCmsEvents(): void
    {
        echo View::render('cms/events');
    }
}
