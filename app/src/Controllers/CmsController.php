<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\View;

class CmsController
{
    public function __construct()
    {
    }

    public function showCmsDashboard(): void
    {
        echo View::render('cms/index');
    }
    public function showCmsPages(): void
    {
        echo View::render('cms/pages');
    }
    public function showCmsComponents(): void
    {
        echo View::render('cms/components');
    }
    public function showCmsTickets(): void
    {
        echo View::render('cms/tickets');
    }
    public function showCmsUsers(): void
    {
        echo View::render('cms/users');
    }
    public function showCmsEvents(): void
    {
        echo View::render('cms/events');
    }

}
