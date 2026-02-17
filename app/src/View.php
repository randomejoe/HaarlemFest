<?php

namespace App;

class View
{
    public static function render(string $template, array $vars = []): string
    {
        $viewPath = __DIR__ . '/Views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            return 'View not found.';
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }
}
