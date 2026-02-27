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
        $currentContent = NULL;
        $varKeys = [];
        foreach ($pageData as $row) {
            if ($currentContent != $row['component_name'] && $currentContent != NULL) {
                $this->addContentToList($returnData, $currentContent, $varKeys);
                $varKeys = [];
            }

            $currentContent = $row['component_name'];

            if ($row['variable_key'] != NULL) {
                $varKeys[] = $row['variable_key'];
            }
        }
        $this->addContentToList($returnData, $currentContent, $varKeys);

        // echo '<pre>';
        // print_r($returnData);
        // echo '</pre>';

        return $returnData;
    }


    private function addContentToList(array &$returnData, string $currentContent, array $varKeys) {
        $returnData['content'][] = ['component_name' => $currentContent, 'keys' => $varKeys];
    }
}
