<?php

namespace App\Services;

interface CMSServiceInterface
{
    public function getForEdit(int $id);

    public function isNameEditable(): bool;

    public function update(int $id, array $postData);

    public function delete(int $id);
}
