<?php

namespace App\Controllers;
use App\View;

class HomeController
{
    public function home($vars = [])
    {
        extract($vars, EXTR_SKIP);
        echo View::render('home');
    }
}
