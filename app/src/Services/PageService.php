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
        try {
            return $this->repository->getAllPages();
        }
        catch (\Throwable $e) {
            return [];
        }
        
    }
    public function getPage($page) {
        try {
            $result = is_numeric($page) ? $this->repository->getPageById($page) : $this->repository->getPageByName(str_replace("_", " ", $page));

            print_r($result);
            
            return $result;
        }
        catch (\Throwable $e) {
            print_r('yooo');
            return null;
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
