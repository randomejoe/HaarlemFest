<?php

namespace App\Services;

use App\Repositories\PageRepository;

class PageService
{
    private PageRepository $repository;

    public function __construct()
    {
        $this->repository = new PageRepository();
    }

    public function getAll() {
        return $this->repository->getAllPages();
    }

    public function create(string $title): bool 
    {
        return $this->repository->createPage($title);
    }

    public function getForEdit(int $id)
    {
        return $this->repository->getPageForEdit($id);
    }
}
