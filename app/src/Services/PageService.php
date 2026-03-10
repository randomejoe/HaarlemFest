<?php

namespace App\Services;

use App\Repositories\PageRepository;

class PageService
{
    private PageRepository $repository;

    public function __construct(PageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllPages() {
        return $this->repository->getAllPages();
    }

    public function createPage(string $title): bool 
    {
        return $this->repository->createPage($title);
    }
}
