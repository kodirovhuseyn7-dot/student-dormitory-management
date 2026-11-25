<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__, 2) . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found';
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }
}