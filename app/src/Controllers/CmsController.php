<?php

namespace App\Controllers;

use App\View;
use App\Services\PageService;
use App\Services\ContentService;

class CmsController
{
    private PageService $pageService;
    private ContentService $contentService;

    public function __construct()
    {
        $this->pageService = new PageService();
        $this->contentService = new ContentService();
    }
    public function resolveService(string $type)
    {
        switch ($type):
            case 'pages':
                return $this->pageService;
                break;
            case 'contents':
                return $this->contentService;
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
        else {
            $this->showCmsPages();
        }
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
            $this->showCmsComponents();
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

    public function showEdit(string $type, int $item_id): void
    {
        $service = $this->resolveService($type);
        $item = $service->getForEdit($item_id);
        $editable = $service->isNameEditable();

        require_once __DIR__ . '/../Views/cms/edit.php';
    }
    public function editItem(string $type, int $item_id)
    {
        $service = $this->resolveService($type);
        $success = $service->update($item_id, $_POST);

        if ($success) {
            if ($type == 'contents' || isset($_POST['newContent'])) {
                if ($type == 'contents') {
                    $pageId = $service->getPageId($item_id);
                }
                else {
                    $pageId = $item_id;
                }
                echo 
                '<script src="/js/redirect.js"></script>
                <script>redirectTo("/cms/pages/' . $pageId . '/edit");</script>';
            } 
            else {
                echo 
                '<script src="/js/redirect.js"></script>
                <script>redirectTo("/cms/' . $type . '");</script>';
            }
            
        }
        else {
            $this->showEdit($type, $item_id);
        }
    }
    public function deleteItem(string $type, int $item_id) 
    {
        $service = $this->resolveService($type);
        $success = $service->delete($item_id);
        
        echo 
        '<script src="/js/redirect.js"></script>
        <script>redirectTo("' . $_POST['return_url'] . '");</script>';
    }
}
