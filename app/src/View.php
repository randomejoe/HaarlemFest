<?php

namespace App;

class View
{
    /** @var null|callable():string */
    private static $csrfTokenResolver = null;

    public static function setCsrfTokenResolver(callable $resolver): void
    {
        self::$csrfTokenResolver = $resolver;
    }

    public static function render(string $template, array $vars = []): string
    {
        $viewPath = __DIR__ . '/Views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            return 'View not found.';
        }

        if (!array_key_exists('csrf_token', $vars) && isset($_SESSION) && self::$csrfTokenResolver !== null) {
            $vars['csrf_token'] = (self::$csrfTokenResolver)();
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }
}
