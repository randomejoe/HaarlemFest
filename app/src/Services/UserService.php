<?php

namespace App\Services;

use App\Repositories\Interfaces\IUserRepository;
class UserService implements CMSServiceInterface
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
    public function update(int $id, array $postData)
    {
        throw new \BadMethodCallException('Unable to edit users through CMS');
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
