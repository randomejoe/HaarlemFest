<?php

/**
 * This is the central route handler of the application.
 * It uses FastRoute to map URLs to controller methods.
 * 
 * See the documentation for FastRoute for more information: https://github.com/nikic/FastRoute
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Container;
use App\View;
use App\Services\CsrfService;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

session_start();

$container = new Container();
View::setCsrfTokenResolver(static function () use ($container): string {
    /** @var CsrfService $csrf */
    $csrf = $container->get(CsrfService::class);
    return $csrf->getToken();
});

/**
 * Define the routes for the application.
 */
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'home']);
    $r->addRoute('GET', '/jazz', ['App\Controllers\JazzController', 'showProgram']);
    $r->addRoute('GET', '/planner', ['App\Controllers\PlannerController', 'show']);
    $r->addRoute('POST', '/planner/items', ['App\Controllers\PlannerController', 'addItem']);
    $r->addRoute('POST', '/planner/items/{eventId}/quantity', ['App\Controllers\PlannerController', 'updateItemQuantity']);
    $r->addRoute('POST', '/planner/items/{eventId}/remove', ['App\Controllers\PlannerController', 'removeItem']);
    $r->addRoute('POST', '/planner/clear', ['App\Controllers\PlannerController', 'clear']);
    $r->addRoute('GET', '/checkout', ['App\Controllers\CheckoutController', 'show']);
    $r->addRoute('POST', '/checkout/details', ['App\Controllers\CheckoutController', 'saveDetails']);
    $r->addRoute('POST', '/checkout/confirm', ['App\Controllers\CheckoutController', 'confirm']);
    $r->addRoute('GET', '/checkout/pending/{checkoutId}', ['App\Controllers\CheckoutController', 'pending']);
    $r->addRoute('POST', '/checkout/pending/{checkoutId}/confirm', ['App\Controllers\CheckoutController', 'confirmPendingPayment']);
    $r->addRoute('GET', '/hello/{name}', ['App\Controllers\HelloController', 'greet']);
    $r->addRoute('GET', '/register', ['App\Controllers\AuthController', 'showRegister']);
    $r->addRoute('POST', '/register', ['App\Controllers\AuthController', 'register']);
    $r->addRoute('GET', '/login', ['App\Controllers\AuthController', 'showLogin']);
    $r->addRoute('POST', '/login', ['App\Controllers\AuthController', 'login']);
    $r->addRoute('POST', '/logout', ['App\Controllers\AuthController', 'logout']);
    $r->addRoute('GET', '/account', ['App\Controllers\AccountController', 'show']);
    $r->addRoute('POST', '/account', ['App\Controllers\AccountController', 'update']);
    $r->addRoute('GET', '/orders', ['App\Controllers\OrdersController', 'show']);
    $r->addRoute('GET', '/password/forgot', ['App\Controllers\PasswordController', 'showForgot']);
    $r->addRoute('POST', '/password/forgot', ['App\Controllers\PasswordController', 'sendReset']);
    $r->addRoute('GET', '/password/reset/{token}', ['App\Controllers\PasswordController', 'showReset']);
    $r->addRoute('POST', '/password/reset/{token}', ['App\Controllers\PasswordController', 'reset']);
    $r->addRoute('GET', '/altcha', ['App\Controllers\AuthController', 'altchaChallenge']);
    $r->addRoute('POST', '/altcha', ['App\Controllers\AuthController', 'altchaChallenge']);
    $r->addRoute('GET', '/cms', ['App\Controllers\CmsController', 'showCmsDashboard']);
    $r->addRoute('GET', '/cms/{type}', ['App\Controllers\CmsController', 'showCmsItems']);
    $r->addRoute('POST', '/cms/{type}', ['App\Controllers\CmsController', 'createCmsItem']);
    $r->addRoute('GET', '/cms/{type}/{id:\d+}/edit', ['App\Controllers\CmsController', 'showEdit']);
    $r->addRoute('POST', '/cms/{type}/{id:\d+}/edit', ['App\Controllers\CmsController', 'editItem']);
    $r->addRoute('POST', '/cms/{type}/{id:\d+}/delete', ['App\Controllers\CmsController', 'deleteItem']);
    $r->addRoute('GET', '/{page}', ['App\Controllers\PageController', 'showPage']);
});


/**
 * Get the request method and URI from the server variables and invoke the dispatcher.
 */
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

/**
 * Switch on the dispatcher result and call the appropriate controller method if found.
 */
switch ($routeInfo[0]) {
    // Handle not found routes
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 'Not Found';
        break;
    // Handle routes that were invoked with the wrong HTTP method
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    // Handle found routes
    case FastRoute\Dispatcher::FOUND:
        /**
         * $routeInfo contains the data about the matched route.
         * 
         * $routeInfo[1] is the whatever we define as the third argument the `$r->addRoute` method.
         *  For instance for: `$r->addRoute('GET', '/hello/{name}', ['App\Controllers\HelloController', 'greet']);`
         *  $routeInfo[1] will be `['App\Controllers\HelloController', 'greet']`
         * 
         * Hint: we can use class strings like `App\Controllers\HelloController` to create new instances of that class.
         * Hint: in PHP we can use a string to call a class method dynamically, like this: `$instance->$methodName($args);`
         */

        [$controllerClass, $method] = $routeInfo[1];
        if ($httpMethod === 'POST' && !in_array($uri, ['/altcha'], true)) {
            /** @var CsrfService $csrf */
            $csrf = $container->get(CsrfService::class);
            if (!$csrf->validate($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                echo 'Forbidden';
                break;
            }
        }

        $controller = $container->get($controllerClass);
        $vars = $routeInfo[2] ?? [];

        $controller->$method(...array_values($vars));

        break;
}
?>
