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

    public function getAllPages() {
        return $this->repository->getAllPages();
    }

    public function createPage(string $title): bool 
    {
        return $this->repository->createPage($title);
    }

    public function getAllComponents() {
        return $this->repository->getAllComponents();
    }

    public function createComponent(string $name): bool 
    {
        return $this->repository->createComponent($name);
    }
}
