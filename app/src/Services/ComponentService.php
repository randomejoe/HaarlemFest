<?php

namespace App\Services;

use App\Repositories\PageRepository;

class ComponentService
{
    private PageRepository $repository;

    public function __construct()
    {
        $this->repository = new PageRepository();
    }

    public function getAll() {
        return $this->repository->getAllComponents();
    }

    public function create(string $name): bool 
    {
        return $this->repository->createComponent($name);
    }

    public function getById(int $id)
    {
        return $this->repository->getComponentById($id);
    }
    public function update(int $id, array $postData): bool
    {
        return $this->repository->updateComponent($id, $postData);
    }
}
