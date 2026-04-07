<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\UserRepository;

class UserService implements CMSService
{
    private UserRepository $repository;
    
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getForEdit(int $id)
    {
        header('Location: /cms/users');
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

    public function findByName(string $userName): array
    {
        return $this->repository->findByName($userName);
    }
}
