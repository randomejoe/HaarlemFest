<?php

namespace App\Controllers;

use App\Services\ContentService;
use App\Services\PageService;
use App\Services\EventService;
use App\Services\LocationService;
use App\Services\UserService;
use App\Services\OrderService;
use App\Services\SessionManager;
use App\View;
use App\Models\UserRole;
use App\Models\CmsType;
use App\Models\FlashType;
class CmsController
{
    private PageService $pageService;
    private ContentService $contentService;
    private EventService $eventService;
    private LocationService $locationService;
    private UserService $userService;
    private OrderService $orderService;

    private SessionManager $sessionManager;

    public function __construct(PageService $pageService, ContentService $contentService, EventService $eventService, LocationService $locationService, UserService $userService, OrderService $orderService, SessionManager $manager)
    {
        $this->pageService = $pageService;
        $this->contentService = $contentService;
        $this->eventService = $eventService;
        $this->locationService = $locationService;
        $this->userService = $userService;
        $this->orderService = $orderService;
        $this->sessionManager = $manager;

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
        try {
            $items = $service->getAll();

            if ($type == CmsType::Event) {
                $categories = $service->getCategories();
            }
            
            if ($items == []) {
                $this->sessionManager->setFlash(FlashType::Error, 'No items could be fetched.');
            }
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to fetch items.');
            $items = [];
        }

        $flash = $this->sessionManager->consumeFlash();
        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type' => $type, 'categories' => $categories ?? null, 'flash' => $flash]);
    }
    public function showItemsByCategory(string $type, string $category): void
    {
        $type = CmsType::convertToType($type);
        $category = urldecode($category);
        $service = $this->resolveService($type);
        try {
            $items = $service->getAllInCategory($category);
            if ($type == CmsType::Event) {
                $categories = $service->getCategories();
            }

            if ($items == []) {
                $this->sessionManager->setFlash(FlashType::Error, 'No items could be fetched.');
            }
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to fetch items.');
            $items = [];
        }
        
        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type' => $type, 'categories' => $categories, 'currentCategory' => $category]);
    }

    public function createCmsItem(string $type): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);

        try {
            $service->create($_POST);
            $this->sessionManager->setFlash(FlashType::Success, 'Successfully added ' . $type->value . ' ' . $_POST['item_name']);
            header('Location: /cms/' . $type->value . 's');
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to add ' . $type->value . ' ' . $_POST['item_name']);
            $this->showCmsItems($type->value);
        }
    }
    public function createItemInCategory(string $type, string $category): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        $category = urldecode($category);

        try {
            $service->createForCategory($category, $_POST);
            $this->sessionManager->setFlash(FlashType::Success, 'Successfully added ' . $type->value . ' ' . $_POST['item_name']);
            header('Location: /cms/' . $type->value . 's/' . $category);
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to add ' . $type->value . ' ' . $_POST['item_name']);
            $this->showItemsByCategory($type->value, $category);
        }
    }

    public function showEdit(string $type, int $item_id): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        try {
            $item = $service->getForEdit($item_id);

            if ($item == null) {
                $this->sessionManager->setFlash(FlashType::Error, $type->value . ' that you tried to edit could not be fetched.');
                header('Location: /cms/' . $type->value . 's');
            }

            $editable = $service->isNameEditable();

            if ($type == CmsType::Event) {
                $categories = $service->getCategories();
            }
            echo View::render('/../Views/cms/edit', ['type' => $type, 'item' => $item, 'editable' => $editable, 'categories' => $categories ?? null]);
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to fetch the selected ' . $type->value);
            header('Location: /cms/' . $type->value . 's');
        }
    }

    public function editItem(string $type, int $item_id)
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        try {
            $this->hasUploadedFiles($_FILES) ?
                $service->updateWithImage($item_id, $_POST, $_FILES) :
                $service->update($item_id, $_POST);

                $this->sessionManager->setFlash(FlashType::Success, 'Successfully edited ' . $type->value . ' ' . $_POST['item_name']);

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
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'Failed to edit ' . $type->value . ' ' . $_POST['item_name']);
            $this->showEdit($type->value, $item_id);
        }
    }

    public function deleteItem(string $type, int $item_id)
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);
        try {
            $service->delete($item_id);
            $this->sessionManager->setFlash(FlashType::Success, 'Successfully deleted selected ' . $type->value);
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'Failed to delete selected ' . $type->value);
        }

        header('Location: ' . $_POST['return_url']);
    }

    private function resolveService(CmsType $type): PageService|ContentService|EventService|LocationService|UserService|OrderService
    {
        return match ($type) {
            CmsType::Page => $this->pageService,
            CmsType::Content => $this->contentService,
            CmsType::Event => $this->eventService,
            CmsType::Location => $this->locationService,
            CmsType::User => $this->userService,
            CmsType::Order => $this->orderService,
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
