<?php

namespace App\Services;

use App\Repositories\PageRepository;

class ContentService
{
    private PageRepository $repository;

    public function __construct()
    {
        $this->repository = new PageRepository();
    }
    public function getForEdit(int $id)
    {
        return $this->repository->getContentForEdit($id);
    }
    public function isNameEditable() {
        return false;
    }
    public function getPageId(int $id) {
        return $this->repository->getContentPageId($id)['page_id'];
    }

    public function update(int $id, array $postData): bool
    {
        return $this->repository->updateContentItem($id, $postData);
    }
}
