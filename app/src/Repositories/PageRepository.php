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

    public function findByTitle(string $title) {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE title = :title LIMIT 1');
        $stmt->execute(['title' => $title]);
        $page = $stmt->fetch();
        return $page;
    }
    public function findById(int $id) {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE page_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $page = $stmt->fetch();
        return $page;
    }

    public function getAllPages() {
        $stmt = $this->pdo->prepare('SELECT title FROM pages');
        $stmt->execute();
        $page = $stmt->fetchAll();
        return $page;
    }
}
