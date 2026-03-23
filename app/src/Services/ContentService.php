<?php

namespace App\Services;

use App\Repositories\PageRepository;
use App\Services\ImageUploader;

class ContentService implements CMSService
{
    private PageRepository $repository;

    public function __construct(PageRepository $repository)
    {
        $this->repository = $repository;
    }
    public function getForEdit(int $id)
    {
        return $this->repository->getContentForEdit($id);
    }
    public function isNameEditable(): bool {
        return false;
    }
    public function getPageId(int $id) {
        return $this->repository->getContentPageId($id)['page_id'];
    }

    public function updateWithImage(int $id, array $postData, array $fileData): bool
    {
        $data = $postData;
        $data['id'] = $id;
        foreach ($fileData as $field => $file) {
            $filename = ImageUploader::handleImageUpload($file);
            $data[$field] = $filename;
        }

        return $this->repository->updateContentItem($id, $data);
    }
    public function update(int $id, array $postData): bool
    {
        return $this->repository->updateContentItem($id, $postData);
    }
    public function delete(int $id): bool {
        return $this->repository->deleteContentItem($id);
    }
}
