<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $callback): void
    {
        $this->add('GET', $path, $callback);
    }

    public function post(string $path, callable $callback): void
    {
        $this->add('POST', $path, $callback);
    }

    public function add(string $method, string $path, callable $callback): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($method);

        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');

        if ($uri === '//') {
            $uri = '/';
        }

        $matchedPath = false;

        foreach ($this->routes as $route) {
            $pattern = preg_replace_callback(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                function ($matches) {
                    return '(?P<' . $matches[1] . '>[^/]+)';
                },
                $route['path']
            );

            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $matchedPath = true;

                if ($route['method'] !== $method) {
                    continue;
                }

                $params = array_filter(
                    $matches,
                    fn($key) => !is_int($key),
                    ARRAY_FILTER_USE_KEY
                );

                call_user_func($route['callback'], $params);
                return;
            }
        }

        if ($matchedPath) {
            http_response_code(405);
            echo '405 Method Not Allowed';
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}