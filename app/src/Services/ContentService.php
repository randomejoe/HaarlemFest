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

    public function update(int $id, array $postData): bool
    {
        //To add
    }
}
