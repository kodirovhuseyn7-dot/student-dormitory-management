<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/helpers.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\StudentController;

$router = new Router();

$homeController = new HomeController();
$studentController = new StudentController();

$router->get('/', [$homeController, 'index']);

$router->get('/students', [$studentController, 'index']);

$router->get('/students/{id}', [$studentController, 'show']);

$router->get('/404', function () {
    http_response_code(404);

    \App\Core\View::render('pages/error', [
        'title' => '404 Not Found',
        'message' => 'The requested page was not found.'
    ]);
});

$router->dispatch(
    $_SERVER['REQUEST_URI'] ?? '/',
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);