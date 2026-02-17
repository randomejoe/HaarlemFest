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
}
