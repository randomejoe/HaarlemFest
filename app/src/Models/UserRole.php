<?php

namespace App\Models;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
