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
        $stmt = $this->pdo->prepare('SELECT title AS item_name FROM pages WHERE page_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $page = $stmt->fetch();
        return $page;
    }

    public function updateComponent(int $id, array $postData): bool
    {
        $stmt = $this->pdo->prepare("UPDATE page_components SET component_name = :component_name, content = :content WHERE component_id = :id");
        $stmt->execute([
            'id' => $id,
            'component_name' => $postData['name'],
            'content' => $postData['content'],
            ]);
        $page = $stmt->fetch();
        return true;
    }
}
