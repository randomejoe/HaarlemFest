<?php

namespace App\Repositories;

use PDO;
use App\Models\UserRole;

class BaseRepository
{

    protected function isAdmin() {
        if (!UserRole::from($_SESSION['role'])->isAdmin()) {
            return false;
        }
        return true;
    }
    protected function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            http_response_code(403);
            exit;
        }
    }
}
