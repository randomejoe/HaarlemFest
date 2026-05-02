<?php

/**
 * This is the central route handler of the application.
 * It uses FastRoute to map URLs to controller methods.
 *
 * See the documentation for FastRoute for more information: https://github.com/nikic/FastRoute
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CheckoutController;
use App\Controllers\CmsController;
use App\Controllers\HomeController;
use App\Controllers\OrdersController;
use App\Controllers\PageController;
use App\Controllers\PasswordController;
use App\Controllers\PlannerController;
use App\Database\Connection;
use App\Repositories\CheckoutRepository;
use App\Repositories\EventRepository;
use App\Repositories\LocationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PageRepository;
use App\Repositories\UserRepository;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\Services\CheckoutService;
use App\Services\ContentService;
use App\Services\CsrfService;
use App\Services\EventService;
use App\Services\InvoicePdfService;
use App\Services\LocationService;
use App\Services\Mailer;
use App\Services\OrderService;
use App\Services\PageRenderer;
use App\Services\PageService;
use App\Services\PasswordResetService;
use App\Services\PdoTransactionManager;
use App\Services\PlannerService;
use App\Services\TicketDeliveryService;
use App\Services\TicketPdfService;
use App\Services\UserService;
use FastRoute\RouteCollector;
use App\View;
use function FastRoute\simpleDispatcher;

session_start();

set_exception_handler(static function (\Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    require __DIR__ . '/../src/Views/error.php';
});

$factories = [];
$singletons = [];

$registerSingleton = static function (string $id, callable $factory) use (&$factories): void {
    $factories[$id] = ['singleton' => true, 'factory' => $factory];
};

$registerTransient = static function (string $id, callable $factory) use (&$factories): void {
    $factories[$id] = ['singleton' => false, 'factory' => $factory];
};

$resolve = function (string $id) use (&$factories, &$singletons, &$resolve): object {
    if (isset($singletons[$id])) {
        return $singletons[$id];
    }

    if (!isset($factories[$id])) {
        throw new \RuntimeException("Service '{$id}' is not registered.");
    }

    $service = ($factories[$id]['factory'])($resolve);

    if ($factories[$id]['singleton']) {
        $singletons[$id] = $service;
    }

    return $service;
};

$registerSingleton(PDO::class, static fn(callable $get): PDO => Connection::get());

$registerSingleton(EventRepository::class, static fn(callable $get): EventRepository => new EventRepository($get(PDO::class)));
$registerSingleton(UserRepository::class, static fn(callable $get): UserRepository => new UserRepository($get(PDO::class)));
$registerSingleton(CheckoutRepository::class, static fn(callable $get): CheckoutRepository => new CheckoutRepository($get(PDO::class)));
$registerSingleton(OrderRepository::class, static fn(callable $get): OrderRepository => new OrderRepository($get(PDO::class)));
$registerSingleton(PageRepository::class, static fn(callable $get): PageRepository => new PageRepository($get(PDO::class)));
$registerSingleton(LocationRepository::class, static fn(callable $get): LocationRepository => new LocationRepository($get(PDO::class)));

$registerSingleton(AuthService::class, static fn(callable $get): AuthService => new AuthService($get(UserRepository::class)));
$registerSingleton(CaptchaService::class, static fn(callable $get): CaptchaService => new CaptchaService());
$registerSingleton(CsrfService::class, static fn(callable $get): CsrfService => new CsrfService());
$registerSingleton(Mailer::class, static fn(callable $get): Mailer => new Mailer());
$registerSingleton(TicketPdfService::class, static fn(callable $get): TicketPdfService => new TicketPdfService());
$registerSingleton(InvoicePdfService::class, static fn(callable $get): InvoicePdfService => new InvoicePdfService());

$registerSingleton(PlannerService::class, static fn(callable $get): PlannerService => new PlannerService(
    $get(EventRepository::class)
));

$registerSingleton(TicketDeliveryService::class, static fn(callable $get): TicketDeliveryService => new TicketDeliveryService(
    $get(Mailer::class),
    $get(TicketPdfService::class),
    $get(InvoicePdfService::class)
));

$registerSingleton(CheckoutService::class, static fn(callable $get): CheckoutService => new CheckoutService(
    $get(PDO::class),
    $get(PlannerService::class),
    $get(CheckoutRepository::class),
    $get(UserRepository::class),
    $get(TicketDeliveryService::class)
));

$registerSingleton(PasswordResetService::class, static fn(callable $get): PasswordResetService => new PasswordResetService(
    $get(UserRepository::class),
    $get(Mailer::class),
    $get(AuthService::class)
));

$registerSingleton(AccountService::class, static fn(callable $get): AccountService => new AccountService(
    $get(UserRepository::class)
));

$registerSingleton(PageService::class, static fn(callable $get): PageService => new PageService(
    $get(PageRepository::class)
));
$registerSingleton(PageRenderer::class, fn(callable $get): PageRenderer => new PageRenderer($resolve));

$registerSingleton(UserService::class, static fn(callable $get): UserService => new UserService(
    $get(UserRepository::class)
));

$registerSingleton(EventService::class, static fn(callable $get): EventService => new EventService(
    $get(EventRepository::class),
    $get(PageRepository::class)
));

$registerSingleton(ContentService::class, static fn(callable $get): ContentService => new ContentService(
    $get(PageRepository::class),
    new PdoTransactionManager($get(PDO::class))
));

$registerSingleton(LocationService::class, static fn(callable $get): LocationService => new LocationService(
    $get(LocationRepository::class)
));

$registerSingleton(OrderService::class, static fn(callable $get): OrderService => new OrderService(
    $get(OrderRepository::class)
));

$registerTransient(HomeController::class, static fn(callable $get): HomeController => new HomeController());
$registerTransient(PageController::class, static fn(callable $get): PageController => new PageController(
    $get(PageRenderer::class),
    $get(PageService::class),
));
$registerTransient(CheckoutController::class, static fn(callable $get): CheckoutController => new CheckoutController(
    $get(CheckoutService::class),
    $get(AuthService::class)
));
$registerTransient(PlannerController::class, static fn(callable $get): PlannerController => new PlannerController(
    $get(PlannerService::class)
));
$registerTransient(AuthController::class, static fn(callable $get): AuthController => new AuthController(
    $get(AuthService::class),
    $get(CaptchaService::class)
));
$registerTransient(AccountController::class, static fn(callable $get): AccountController => new AccountController(
    $get(AccountService::class),
    $get(AuthService::class)
));
$registerTransient(OrdersController::class, static fn(callable $get): OrdersController => new OrdersController(
    $get(AuthService::class),
    $get(OrderService::class)
));
$registerTransient(PasswordController::class, static fn(callable $get): PasswordController => new PasswordController(
    $get(PasswordResetService::class)
));
$registerTransient(CmsController::class, static fn(callable $get): CmsController => new CmsController(
    $get(PageService::class),
    $get(ContentService::class),
    $get(EventService::class),
    $get(LocationService::class),
    $get(UserService::class),
    $get(OrderService::class),
));

View::setCsrfTokenResolver(static function () use ($resolve): string {
    /** @var CsrfService $csrf */
    $csrf = $resolve(CsrfService::class);
    return $csrf->getToken();
});

/**
 * Define the routes for the application.
 */
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'home']);
    $r->addRoute('GET', '/planner', ['App\Controllers\PlannerController', 'show']);
    $r->addRoute('POST', '/planner/items', ['App\Controllers\PlannerController', 'addItem']);
    $r->addRoute('POST', '/planner/items/{eventId}/quantity', ['App\Controllers\PlannerController', 'updateItemQuantity']);
    $r->addRoute('POST', '/planner/items/{eventId}/remove', ['App\Controllers\PlannerController', 'removeItem']);
    $r->addRoute('POST', '/planner/clear', ['App\Controllers\PlannerController', 'clear']);
    $r->addRoute('GET', '/checkout', ['App\Controllers\CheckoutController', 'show']);
    $r->addRoute('POST', '/checkout/details', ['App\Controllers\CheckoutController', 'saveDetails']);
    $r->addRoute('POST', '/checkout/confirm', ['App\Controllers\CheckoutController', 'confirm']);
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
    $r->addRoute('GET', '/cms/{type}/{category}', ['App\Controllers\CmsController', 'showItemsByCategory']);
    $r->addRoute('POST', '/cms/{type}/{category}', ['App\Controllers\CmsController', 'createItemInCategory']);
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
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 'Not Found';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        [$controllerClass, $method] = $routeInfo[1];
        if ($httpMethod === 'POST' && !in_array($uri, ['/altcha'], true)) {
            /** @var CsrfService $csrf */
            $csrf = $resolve(CsrfService::class);
            if (!$csrf->validate($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                echo 'Forbidden';
                break;
            }
        }

        $controller = $resolve($controllerClass);
        $vars = $routeInfo[2] ?? [];

        $controller->$method(...array_values($vars));

        break;
}
