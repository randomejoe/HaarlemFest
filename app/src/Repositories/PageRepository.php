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
    public function getAllComponents() 
    {
        $stmt = $this->pdo->prepare('SELECT component_name, component_id AS id FROM page_components');
        $stmt->execute();
        $components = $stmt->fetchAll();
        return $components;
    }

    public function createComponent(string $name): bool 
    {
        $stmt = $this->pdo->prepare('INSERT INTO page_components (component_name, content) VALUES (:component_name, :content)');
        $stmt->execute([
            'component_name' => $name,
            'content' => '<div></div>'
        ]);
        return true;
    }
    public function createPage(string $title): bool 
    {
        $stmt = $this->pdo->prepare('INSERT INTO pages (title) VALUES (:title)');
        $stmt->execute(['title' => $title]);
        return true;
    }

    public function getComponentForEdit(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT component_name AS item_name, content FROM page_components WHERE component_id = :id');
        $stmt->execute(['id' => $id]);
        $component = $stmt->fetch();
        return $component;
    }
    public function getPageForEdit(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.title as item_name, c.component_name, k.variable_key
            FROM pages p
            LEFT JOIN page_content pc ON p.page_id = pc.page_id
            LEFT JOIN page_components c ON c.component_id = pc.component_id
            LEFT JOIN page_component_variable_keys k 
            ON k.component_id = c.component_id
            WHERE p.page_id = :page_id"
            );
        $stmt->execute(['page_id' => $id]);
        $page = $stmt->fetchAll();
        return $page;
    }

    public function updateComponent(int $id, array $data): bool
    {
        try {
            $this->pdo->beginTransaction();
            var_dump($data);
            var_dump($data['keys']);
            $placeholders = implode(',', array_fill(0, count($data['keys']), '?'));
            $stmt = $this->pdo->prepare(
                "DELETE FROM page_component_variable_keys 
                WHERE component_id = ? 
                AND variable_key NOT IN ($placeholders)");
            $stmt->execute(array_merge([$id], $data['keys']));
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO page_component_variable_keys (component_id, variable_key)
                VALUES (:id, :variable_key)
                ON DUPLICATE KEY UPDATE variable_key = variable_key"
            );

            foreach ($data['keys'] as $key) {
                $stmt->execute([
                    'id' => $id,
                    'variable_key' => $key
                ]);
            }

            $stmt = $this->pdo->prepare("UPDATE page_components SET component_name = :component_name, content = :content WHERE component_id = :id");
            $stmt->execute([
                'id' => $id,
                'component_name' => $data['name'],
                'content' => $data['content'],
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
}
