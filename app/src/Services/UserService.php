<?php

namespace App\Services;

use App\Repositories\Interfaces\IUserRepository;
use App\Services\Interfaces\ICmsService;

class UserService implements ICmsService
{
    private IUserRepository $repository;

    public function __construct(IUserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getForEdit(int $id)
    {
        return null;
    }

    public function isNameEditable(): bool
    {
        return false;
    }

    // Update can't be done in cms, users update their own data.
    public function update(int $id, array $postData): bool
    {
        return false;
    }

    public function delete(int $id): bool
    {
        return $this->repository->deleteUser($id);
    }

    public function getAll()
    {
        return $this->repository->getAllUsers();
    }

}
