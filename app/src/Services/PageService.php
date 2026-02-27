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

    public function getAll() {
        return $this->repository->getAllPages();
    }

    public function create(string $title): bool 
    {
        return $this->repository->createPage($title);
    }

    public function getForEdit(int $id)
    {
        $pageData = $this->repository->getPageForEdit($id);
        $returnData = ['item_name' => $pageData[0]['item_name']];
        $currentContent = ['component_name' => NULL];
        $vars = [];
        foreach ($pageData as $row) {
            if ($currentContent['component_name'] != $row['component_name'] && $currentContent['component_name'] != NULL) {
                $this->addContentToList($returnData, $currentContent, $vars);
                $vars = [];
            }

            $currentContent = $row;

            if ($row['variable_key'] != NULL) {
                $vars[] = ['key' => $row['variable_key'], 'id' => $row['variable_key_id'], 'value' => $row['variable_value']];
            }
        }
        $this->addContentToList($returnData, $currentContent, $vars);

        return $returnData;
    }

    private function addContentToList(array &$returnData, array $currentContent, array $vars) {
        $returnData['content'][] = ['component_name' => $currentContent['component_name'], 'id' => $currentContent['component_id'], 'variables' => $vars];
    }

    public function update(int $id, array $postData) {
        return $this->repository->updatePage($id, $postData);
    }
}
