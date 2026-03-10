<?php

namespace App\Services;

interface CMSService
{
    public function getForEdit(int $id);

    public function isNameEditable(): bool;

    public function update(int $id, array $postData): bool;

    public function delete(int $id): bool;
}
