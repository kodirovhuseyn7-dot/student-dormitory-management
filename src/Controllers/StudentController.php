<?php

namespace App\Controllers;

use App\Core\View;

class StudentController
{
    public function index(): void
    {
        View::render('pages/students', [
            'title' => 'Students',
            'students' => [
                ['id' => 1, 'name' => 'Student One'],
                ['id' => 2, 'name' => 'Student Two'],
                ['id' => 3, 'name' => 'Student Three']
            ]
        ]);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        View::render('pages/student', [
            'title' => 'Student Details',
            'student' => [
                'id' => $id,
                'name' => 'Student #' . $id
            ]
        ]);
    }
}