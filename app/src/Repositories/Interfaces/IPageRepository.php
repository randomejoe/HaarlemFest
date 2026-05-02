<?php

namespace App\Repositories\Interfaces;

interface IPageRepository
{
    public function getAllPages(): array;

    public function getContentPageId(int $id);

    public function getPageById(int $id);

    public function getPageByName(string $name);

    public function createPage(string $title, int $isMainEvent): bool;

    public function getPageForEdit(int $id);

    public function getContentForEdit(int $id);

    public function updatePage(int $id, array $data): bool;

    public function addContentItemToPage(int $pageId, string $componentName);

    public function assertAdmin(): void;

    public function updateContentItem(int $id, string $encodedJson): bool;

    public function deletePage(int $pageId);

    public function deleteContentItem(int $contentId);

    public function getEventCategories();
}
