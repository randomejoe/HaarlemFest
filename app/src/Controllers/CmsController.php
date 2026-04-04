<?php

namespace App\Controllers;

use App\Services\ContentService;
use App\Services\PageService;
use App\Services\EventService;
use App\Services\LocationService;
use App\View;
use App\Models\UserRole;
use App\Models\CmsType;

class CmsController
{
    private PageService $pageService;
    private ContentService $contentService;
    private EventService $eventService;
    private LocationService $locationService;

    public function __construct(PageService $pageService, ContentService $contentService, EventService $eventService, LocationService $locationService)
    {
        $this->pageService = $pageService;
        $this->contentService = $contentService;
        $this->eventService = $eventService;
        $this->locationService = $locationService;
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

        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type' => $type, 'categories' => $categories ?? null]);
    }
    public function showItemsByCategory(string $type, string $category): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $category = urldecode($category);
        $items = $service->getAllInCategory($category);
        if ($type == CmsType::Event) {
            $categories = $service->getCategories();
        }

        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type' => $type, 'categories' => $categories, 'currentCategory' => $category]);
    }

    public function createCmsItem(string $type): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);

        $success = $service->create($_POST);

        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $_POST['item_name'];
            header('Location: /cms/' . $type->value . 's');
        } else {
            $this->showCmsItems($type->value);
        }
    }
    public function createItemInCategory(string $type, string $category): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $category = urldecode($category);

        $success = $service->createForCategory($category, $_POST);

        if ($success) {
            $_SESSION['create_success'] = true;
            $_SESSION['create_title'] = $_POST['item_name'];
            header('Location: /cms/' . $type->value . 's/' . $category);
        } else {
            $this->showItemsByCategory($type->value, $category);
        }
    }

    public function showEdit(string $type, int $item_id): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $item = $service->getForEdit($item_id);
        $editable = $service->isNameEditable();

        if ($type == CmsType::Event) {
            $categories = $service->getCategories();
        }

        echo View::render('/../Views/cms/edit', ['type' => $type, 'item' => $item, 'editable' => $editable, 'categories' => $categories ?? null]);
    }
    public function editItem(string $type, int $item_id)
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        if ($this->hasUploadedFiles($_FILES)) {
            $success = $service->updateWithImage($item_id, $_POST, $_FILES);
        } else {
            $success = $service->update($item_id, $_POST);
        }

        if ($success) {
            if ($type == CmsType::Content || isset($_POST['newContent'])) {
                if ($type == CmsType::Content) {
                    $pageId = $service->getPageId($item_id);
                } else {
                    $pageId = $item_id;
                }
                header('Location: /cms/pages/' . $pageId . '/edit');
            } else {
                header('Location: /cms/' . $type->value);
            }
        } else {
            $this->showEdit($type->value, $item_id);
        }
    }
    public function deleteItem(string $type, int $item_id)
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $service->delete($item_id);

        header('Location: ' . $_POST['return_url']);
    }

    private function resolveService(CmsType $type): PageService|ContentService|EventService|LocationService
    {
        return match ($type) {
            CmsType::Page => $this->pageService,
            CmsType::Content => $this->contentService,
            CmsType::Event => $this->eventService,
            CmsType::Location => $this->locationService,
            default => throw new \InvalidArgumentException('Unknown CMS type.'),
        };
    }

    private function hasUploadedFiles(array $files): bool
    {
        $hasFiles = false;
        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $hasFiles =  true;
            }
        }

        return $hasFiles;
    }
}
