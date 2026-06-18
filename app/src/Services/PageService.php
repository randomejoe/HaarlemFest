<?php

namespace App\Services;

use App\Repositories\Interfaces\IPageRepository;

class PageService implements CMSServiceInterface
{
    private IPageRepository $repository;

    public function __construct(IPageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll() {
        return $this->repository->getAllPages();
    }
    public function getPage($page) {
        try {
            $result = is_numeric($page) ? $this->repository->getPageById($page) : $this->repository->getPageByName(str_replace("_", " ", $page));
            
            return $result;
        }
        catch (\Throwable $e) {
            return null;
        }
        
    }

    public function create(array $postData): bool 
    {
        $title = trim((string) ($postData['item_name'] ?? ''));
        $isMainEvent = ($postData['is_main_event'] ?? "off") == "on" ? 1 : 0;
        $this->repository->createPage($title, $isMainEvent);
    }
    public function isNameEditable(): bool {
        return true;
    }

    public function getForEdit(int $id)
    {
        try {
            return $this->repository->getPageForEdit($id);
        }
        catch (\Throwable $e) {
            return null;
        }
    }

    public function update(int $id, array $postData) {
        if (isset($postData['newContent'])) {
            $this->repository->addContentItemToPage($id, $postData['newContent']);
        }
        else  {
            $this->repository->updatePage($id, $postData);
        }
    }
    public function delete(int $id) {
        $this->repository->deletePage($id);
    }
}
