<?php

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = '/student-dormitory-management-github/public';

    if ($path === '/') {
        return $base . '/';
    }

    return $base . '/' . ltrim($path, '/');
}