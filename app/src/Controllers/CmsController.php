<?php

namespace App\Controllers;

use App\Services\ContentService;
use App\Services\PageService;

class CmsController
{
    private PageService $pageService;
    private ContentService $contentService;

    public function __construct(PageService $pageService, ContentService $contentService)
    {
        $this->pageService = $pageService;
        $this->contentService = $contentService;
    }

    public function showCmsDashboard(): void
    {
        require(__DIR__ . '/../Views/cms/index.php');
    }

    public function showCmsPages(): void
    {
        $pages = $this->pageService->getAll();

        require_once __DIR__ . '/../Views/cms/pages.php';
    }

    public function createPage(): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $success = $this->pageService->create($title);
        
        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $title;
            header('Location: /cms/pages');
        }
        else {
            $this->showCmsPages();
        }
    }

    public function showCmsComponents(): void
    {
        $components = [];
        require_once __DIR__ . '/../Views/cms/components.php';
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
                header('Location: /cms/pages/' . $pageId . '/edit');
            } 
            else {
                header('Location: /cms/' . $type);
            }
            
        }
        else {
            $this->showEdit($type, $item_id);
        }
    }
    public function deleteItem(string $type, int $item_id) 
    {
        $service = $this->resolveService($type);
        $service->delete($item_id);

        header('Location: ' . $_POST['return_url']);
    }

    private function resolveService(string $type): PageService|ContentService
    {
        return match ($type) {
            'pages' => $this->pageService,
            'contents' => $this->contentService,
            default => throw new \InvalidArgumentException('Unknown CMS type.'),
        };
    }
}
