<?php

namespace App\Controllers;

class HomeController
{
    public function home($vars = [])
    {
        extract($vars, EXTR_SKIP);
        require(__DIR__ . '/../Views/home.php');
    }
}
