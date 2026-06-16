<?php

namespace App\Controllers;

use App\Services\ContentService;
use App\Services\PageService;
use App\Services\LocationService;
use App\Services\UserService;
use App\Services\OrderService;
use App\Services\Interfaces\IEventService
    as EventSvc;
use App\Services\SessionManager;
use App\View;
use App\Models\UserRole;
use App\Models\CmsType;
use App\Models\FlashType;
class CmsController
{
    private PageService $pageService;
    private ContentService $contentService;
    private EventSvc $eventService;
    private LocationService $locationService;
    private UserService $userService;
    private OrderService $orderService;

    private SessionManager $sessionManager;

    public function __construct(PageService $pageService, ContentService $contentService, EventSvc $eventService, LocationService $locationService, UserService $userService, OrderService $orderService, SessionManager $manager)
    {
        $this->pageService = $pageService;
        $this->contentService = $contentService;
        $this->eventService = $eventService;
        $this->locationService = $locationService;
        $this->userService = $userService;
        $this->orderService = $orderService;
        $this->sessionManager = $manager;

        if ($this->requireAdmin()) {
            header('Location: /');
            exit;
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

        $params = $_GET;
        try {
            $items = $service->getAll($params);

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
        echo View::render('/../Views/cms/item_list', ['items' => $items, 'type' => $type, 'categories' => $categories ?? null, 'flash' => $flash, 'params' => $params]);
    }

    public function createCmsItem(string $type): void
    {
        $type = CmsType::convertToType($type);
        $service = $this->resolveService($type);

        try {

            if (isset($_GET['event'])) {
                $category = $_GET['event'];
                $service->createForCategory($category, $_POST);
                $this->sessionManager->setFlash(FlashType::Success, 'Successfully added ' . $type->value . ' ' . $_POST['item_name']);
                header('Location: /cms/' . $type->value . 's?event=' . $category);
                exit;
            }
            else {
                $service->create($_POST);
                $this->sessionManager->setFlash(FlashType::Success, 'Successfully added ' . $type->value . ' ' . $_POST['item_name']);
                header('Location: /cms/' . $type->value . 's');
                exit;
            }
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to add ' . $type->value . ' ' . $_POST['item_name']);
            $this->showCmsItems($type->value);
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
                exit;
            }

            $editable = $service->isNameEditable();

            if ($type == CmsType::Event) {
                $categories = $service->getCategories();
            }
            echo View::render('/../Views/cms/edit', ['type' => $type, 'item' => $item, 'editable' => $editable, 'categories' => $categories ?? null]);
        }
        catch (\Throwable $e) {
            $this->sessionManager->setFlash(FlashType::Error, 'An error occurred while trying to fetch the selected ' . $type->value);
            print_r($e);
            header('Location: /cms/' . $type->value . 's');
            exit;
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
                exit;
            } else {
                header('Location: /cms/' . $type->value);
                exit;
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
        exit;
    }

    private function resolveService(CmsType $type): PageService|ContentService|EventSvc|LocationService|UserService|OrderService
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

    private function isAdmin() {
        return isset($_SESSION['role']) ? UserRole::from($_SESSION['role'])->isAdmin() : false;
    }
    private function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            http_response_code(403);
            header("Location: /");
            exit;
        }
    }
}
