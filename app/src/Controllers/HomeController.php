<?php

namespace App\Controllers;

use App\View;

class HomeController
{
    public function home($vars = [])
    {
        echo View::render('home', $vars);
    }
}
