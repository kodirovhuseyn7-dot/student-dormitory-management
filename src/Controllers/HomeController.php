<?php

namespace App\Controllers;

use App\Core\View;

class HomeController
{
    public function index(): void
    {
        View::render('pages/home', [
            'title' => 'Student Dormitory Management System',
            'message' => 'Backend routing with Composer and PHP'
        ]);
    }
}