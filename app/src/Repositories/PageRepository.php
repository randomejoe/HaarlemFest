<?php

namespace App\Repositories;

use PDO;

class PageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPages() 
    {
        $stmt = $this->pdo->prepare('SELECT title, page_id AS id FROM pages');
        $stmt->execute();
        $pages = $stmt->fetchAll();
        return $pages;
    }
    public function getContentPageId(int $id) {
        $stmt = $this->pdo->prepare('SELECT page_id FROM page_content WHERE content_id = :content_id');
        $stmt->execute(['content_id' => $id]);
        $pageId = $stmt->fetch();
        return $pageId;
    }
    public function getPageById(int $id) {
        $stmt = $this->pdo->prepare('SELECT * FROM pages JOIN page_content pc ON pc.page_id = pages.page_id WHERE pages.page_id = :id');
        $stmt->execute(['id' => $id]);
        $pageContent = $stmt->fetch();
        return $pageContent;
    }
    public function getPageByName(string $name) {
        $stmt = $this->pdo->prepare('SELECT * FROM pages JOIN page_content pc ON pc.page_id = pages.page_id WHERE LOWER(title) = LOWER(:title)');
        $stmt->execute(['title' => $name]);
        $pageContent = $stmt->fetchAll();
        return $pageContent;
    }

    public function createPage(string $title): bool 
    {
        $stmt = $this->pdo->prepare('INSERT INTO pages (title) VALUES (:title)');
        $stmt->execute(['title' => $title]);
        return true;
    }

    public function getPageForEdit(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.title as item_name, pc.content_id, pc.component_name, pc.data
            FROM pages p
            LEFT JOIN page_content pc ON p.page_id = pc.page_id
            WHERE p.page_id = :page_id"
            );
        $stmt->execute(['page_id' => $id]);
        $page = $stmt->fetchAll();
        return $page;
    }
    public function getContentForEdit(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT content_id, page_id, component_name as item_name, data
            FROM page_content pc
            WHERE pc.content_id = :content_id"
            );
        $stmt->execute(['content_id' => $id]);
        $component = $stmt->fetch();
        return $component;
    }

    public function updatePage(int $id, array $data): bool
    {
            // Update page name
            $stmt = $this->pdo->prepare("UPDATE pages SET title = :title WHERE page_id = :id");
            $stmt->execute([
                'id' => $id,
                'title' => $data['name'],
            ]);
            return true;
    }
    public function addContentItemToPage(int $pageId, string $componentName) {
        $stmt = $this->pdo->prepare("INSERT INTO page_content (page_id, component_name) VALUES (:page_id, :component_name)");
        $stmt->execute([
            'page_id' => $pageId,
            'component_name' => $componentName,
        ]);
    }
    public function updateContentItem(int $id, array $data): bool
    {
        try {
            $this->pdo->beginTransaction();
            unset($data['name']);

            // Update content data
            $stmt = $this->pdo->prepare("UPDATE page_content SET data = :data WHERE content_id = :id");
            $stmt->execute([
                'id' => $id,
                'data' => strip_tags(json_encode($data)),
            ]);

            $this->pdo->commit();
            return true;
        }
        catch (Exception $e) {
            echo $e;
            $this->pdo->rollback();
            return false;
        }
    }
    public function deletePage(int $pageId) {
        $stmt = $this->pdo->prepare("DELETE FROM pages WHERE page_id = :page_id");
        $stmt->execute([
            'page_id' => $pageId
        ]);
        return true;
    }
    public function deleteContentItem(int $contentId) {
        $stmt = $this->pdo->prepare("DELETE FROM page_content WHERE content_id = :content_id");
        $stmt->execute([
            'content_id' => $contentId
        ]);
        return true;
    }
}
