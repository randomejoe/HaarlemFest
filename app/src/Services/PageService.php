<?php

namespace App\Services;

use App\Repositories\PageRepository;

class PageService implements CMSServiceInterface
{
    private PageRepository $repository;

    public function __construct(PageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll() {
        return $this->repository->getAllPages();
    }
    public function getPage($page) {
        if (is_numeric($page)) {
            return $this->repository->getPageById($page);
        }
        else {
            $pageName = str_replace("_", " ", $page);
            return $this->repository->getPageByName($pageName);
        }
    }

    public function create(array $postData): bool 
    {
        $title = trim((string) ($postData['item_name'] ?? ''));
        $isMainEvent = ($postData['is_main_event'] ?? "off") == "on" ? 1 : 0;
        return $this->repository->createPage($title, $isMainEvent);
    }
    public function isNameEditable(): bool {
        return true;
    }

    public function getForEdit(int $id)
    {
       return $this->repository->getPageForEdit($id);
    }

    public function update(int $id, array $postData): bool {
        if (isset($postData['newContent'])) {
            return $this->repository->addContentItemToPage($id, $postData['newContent']);
        }
        else  {
            return $this->repository->updatePage($id, $postData);
        }
    }
    public function delete(int $id): bool {
        return $this->repository->deletePage($id);
    }
}
