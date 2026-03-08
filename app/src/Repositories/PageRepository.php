<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class PageRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    public function getAllPages() 
    {
        $stmt = $this->pdo->prepare('SELECT title, page_id AS id FROM pages');
        $stmt->execute();
        $pages = $stmt->fetchAll();
        return $pages;
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
            "SELECT content_id, component_name as item_name, data
            FROM page_content pc
            WHERE pc.content_id = :content_id"
            );
        $stmt->execute(['content_id' => $id]);
        $component = $stmt->fetch();
        return $component;
    }

    public function updatePage(int $id, array $data): bool
    {
        try {
            $this->pdo->beginTransaction();

            echo '<pre>';
            print_r($data);
            echo '</pre>';

            // Update page name
            $stmt = $this->pdo->prepare("UPDATE pages SET title = :title WHERE page_id = :id");
            $stmt->execute([
                'id' => $id,
                'title' => $data['name'],
            ]);

            // Update data

            $this->pdo->commit();
            return false;
        }
        catch (Exception $e) {
            echo $e;
            $this->pdo->rollback();
            return false;
        }
    }
}
