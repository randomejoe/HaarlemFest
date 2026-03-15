<?php

namespace App\Controllers;

use App\Services\ContentService;
use App\Services\PageService;
use App\Services\EventService;
use App\View;
use App\Models\UserRole;
use App\Models\CmsType;

class CmsController
{
    private PageService $pageService;
    private ContentService $contentService;
    private EventService $eventService;

    public function __construct(PageService $pageService, ContentService $contentService, EventService $eventService)
    {
        $this->pageService = $pageService;
        $this->contentService = $contentService;
        $this->eventService = $eventService;
        if (!isset($_SESSION['role'])) {
            header('Location: /');  
        }
        $role = UserRole::from($_SESSION['role']);
        if (!isset($role) || !$role->isAdmin()) {
            header('Location: /');   
        }
    }

    public function showCmsDashboard(): void
    {
        echo View::render('/../Views/cms/index');
    }

    public function showCmsItems(string $type): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $items = $service->getAll();
        if ($type == CmsType::Event) {
            $categories = $service->getCategories();
        }

        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type'=>$type, 'categories'=>$categories ?? null]);
    }
    public function showCmsItemsInCategory(string $type, string $category): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $items = $service->getAllInCategory($category);
        if ($type == CmsType::Event) {
            $categories = $service->getCategories();
        }

        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type'=>$type,'categories'=>$categories, 'currentCategory'=>$category]);
    }

    public function createCmsItem(string $type): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);

        $success = $service->create($_POST);
        
        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $_POST['item_name'];
            header('Location: /cms/' . $type->value);
        }
        else {
            $this->showCmsItems($type->value);
        }
    }
    public function createCmsItemWithCategory(string $type, string $category): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);

        $success = $service->createForCategory($category, $_POST);
        
        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $_POST['item_name'];
            header('Location: /cms/' . $type->value . '/' . $category);
        }
        else {
            $this->showCmsItemsInCategory();
        }
    }

    public function showEdit(string $type, int $item_id): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $item = $service->getForEdit($item_id);
        $editable = $service->isNameEditable();

        echo View::render('/../Views/cms/edit', ['type'=>$type, 'item'=>$item, 'editable'=>$editable]);
    }
    public function editItem(string $type, int $item_id)
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $success = $service->update($item_id, $_POST);

        if ($success) {
            if ($type == CmsType::Content || isset($_POST['newContent'])) {
                if ($type == CmsType::Content) {
                    $pageId = $service->getPageId($item_id);
                }
                else {
                    $pageId = $item_id;
                }
                header('Location: /cms/pages/' . $pageId . '/edit');
            } 
            else {
                header('Location: /cms/' . $type->value);
            }
            
        }
        else {
            $this->showEdit($type, $item_id);
        }
    }
    public function deleteItem(string $type, int $item_id) 
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $service->delete($item_id);

        header('Location: ' . $_POST['return_url']);
    }

    private function resolveService(CmsType $type): PageService|ContentService|EventService
    {
        return match ($type) {
            CmsType::Page => $this->pageService,
            CmsType::Content => $this->contentService,
            CmsType::Event=>$this->eventService,
            default => throw new \InvalidArgumentException('Unknown CMS type.'),
        };
    }
}
