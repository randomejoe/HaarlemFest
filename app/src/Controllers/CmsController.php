<?php

namespace App\Controllers;

use App\View;
use App\Services\PageService;
use App\Services\ComponentService;

class CmsController
{
    private PageService $pageService;
    private ComponentService $componentService;

    public function __construct()
    {
        $this->pageService = new PageService();
        $this->componentService = new ComponentService();
    }
    public function resolveService(string $type)
    {
        switch ($type):
            case 'components':
                return $this->componentService;
                break;
            case 'pages':
                return $this->pageService;
                break;
            endswitch;
    }

    public function showCmsDashboard(): void
    {
        echo View::render('cms/index');
    }
    public function showCmsPages(): void
    {
        $pages = $this->pageService->getAll();

        require_once __DIR__ . '/../Views/cms/pages.php';
    }
    public function createPage() {
        $title = $_POST['title'];
        $success = $this->pageService->create($title);
        
        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $title;
            echo 
            '<script src="/js/redirect.js"></script>
            <script>redirectTo("/cms/pages");</script>';
        }
        require_once __DIR__ . '/../Views/cms/pages.php';
    }
    public function showCmsComponents(): void
    {
        $components = $this->componentService->getAll();

        require_once __DIR__ . '/../Views/cms/components.php';

    }
    public function createComponent() {
        $name = $_POST['component_name'];
        $success = $this->componentService->create($name);
        
        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $name;
            echo 
            '<script src="/js/redirect.js"></script>
            <script>redirectTo("/cms/components");</script>';
        }
        else {
            showCmsComponents();
        }
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

    public function showEdit(string $type, int $id): void
    {
        $service = $this->resolveService($type);
        $item = $service->getForEdit($id);

        require_once __DIR__ . '/../Views/cms/edit.php';
    }
    public function editItem(string $type, int $id)
    {
        var_dump($_POST);
        $service = $this->resolveService($type);
        $success = $service->update($id, $_POST);

        if ($success) {
            echo 
            '<script src="/js/redirect.js"></script>
            <script>redirectTo("/cms/' . $type . '");</script>';
        }
        else {
            showEdit($type, $id);
        }
    }
}
