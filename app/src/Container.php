<?php

namespace App;

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
use App\Repositories\OrderRepository;
use App\Repositories\PageRepository;
use App\Repositories\TicketHoldRepository;
use App\Repositories\UserRepository;
use App\Repositories\LocationRepository;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\Services\CheckoutAttemptStateMachine;
use App\Services\CheckoutService;
use App\Services\CheckoutHoldManager;
use App\Services\CheckoutValidationService;
use App\Services\ContentService;
use App\Services\CsrfService;
use App\Services\EventService;
use App\Services\InvoicePdfService;
use App\Services\Mailer;
use App\Services\PageService;
use App\Services\PasswordResetService;
use App\Services\PaymentGatewayStubService;
use App\Services\SessionManager;
use App\Services\DateTimeFormatter;
use App\Services\HoldExpiryEvaluator;
use App\Services\ExpiryCleanupLogger;
use App\Services\StockReservationService;
use App\Services\PlannerService;
use App\Services\TicketDeliveryOrchestrator;
use App\Services\TicketDeliveryService;
use App\Services\TicketPdfService;
use App\Services\PaymentHandoffService;
use App\Services\LocationService;
use PDO;
use RuntimeException;

class Container
{
    /** @var array<string, callable(self):object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $singletons = [];

    /** @var array<string, true> */
    private array $singletonIds = [];

    public function __construct()
    {
        $this->register();
    }

    public function get(string $id): object
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new RuntimeException("Service '{$id}' is not registered.");
        }

        $service = ($this->factories[$id])($this);

        if (isset($this->singletonIds[$id])) {
            $this->singletons[$id] = $service;
        }

        return $service;
    }

    private function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletonIds[$id] = true;
    }

    private function transient(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    private function register(): void
    {
        $this->singleton(PDO::class, static fn(self $c): PDO => Connection::get());

        $this->singleton(EventRepository::class, fn(self $c): EventRepository => new EventRepository($c->pdo()));
        $this->singleton(UserRepository::class, fn(self $c): UserRepository => new UserRepository($c->pdo()));
        $this->singleton(CheckoutRepository::class, fn(self $c): CheckoutRepository => new CheckoutRepository($c->pdo()));
        $this->singleton(OrderRepository::class, fn(self $c): OrderRepository => new OrderRepository($c->pdo()));
        $this->singleton(TicketHoldRepository::class, fn(self $c): TicketHoldRepository => new TicketHoldRepository($c->pdo()));
        $this->singleton(PageRepository::class, fn(self $c): PageRepository => new PageRepository($c->pdo()));
        $this->singleton(LocationRepository::class, fn(self $c): LocationRepository => new LocationRepository($c->pdo()));

        $this->singleton(AuthService::class, static fn(self $c): AuthService => new AuthService());
        $this->singleton(CaptchaService::class, static fn(self $c): CaptchaService => new CaptchaService());
        $this->singleton(CsrfService::class, static fn(self $c): CsrfService => new CsrfService());
        $this->singleton(Mailer::class, static fn(self $c): Mailer => new Mailer());
        $this->singleton(TicketPdfService::class, static fn(self $c): TicketPdfService => new TicketPdfService());
        $this->singleton(InvoicePdfService::class, static fn(self $c): InvoicePdfService => new InvoicePdfService());
        $this->singleton(PaymentGatewayStubService::class, static fn(self $c): PaymentGatewayStubService => new PaymentGatewayStubService());
        $this->singleton(DateTimeFormatter::class, static fn(self $c): DateTimeFormatter => new DateTimeFormatter());
        $this->singleton(SessionManager::class, static fn(self $c): SessionManager => new SessionManager());
        $this->singleton(HoldExpiryEvaluator::class, fn(self $c): HoldExpiryEvaluator => new HoldExpiryEvaluator(
            $c->get(DateTimeFormatter::class)
        ));
        $this->singleton(ExpiryCleanupLogger::class, static fn(self $c): ExpiryCleanupLogger => new ExpiryCleanupLogger());
        $this->singleton(CheckoutAttemptStateMachine::class, static fn(self $c): CheckoutAttemptStateMachine => new CheckoutAttemptStateMachine());

        $this->singleton(PlannerService::class, fn(self $c): PlannerService => new PlannerService(
            $c->get(EventRepository::class),
            $c->get(SessionManager::class)
        ));
        $this->singleton(CheckoutValidationService::class, fn(self $c): CheckoutValidationService => new CheckoutValidationService(
            $c->get(PlannerService::class)
        ));
        $this->singleton(StockReservationService::class, fn(self $c): StockReservationService => new StockReservationService(
            $c->get(EventRepository::class),
            $c->get(TicketHoldRepository::class),
            $c->get(DateTimeFormatter::class),
            $c->pdo()
        ));
        $this->singleton(CheckoutHoldManager::class, fn(self $c): CheckoutHoldManager => new CheckoutHoldManager(
            $c->get(TicketHoldRepository::class),
            $c->get(CheckoutRepository::class),
            $c->get(EventRepository::class),
            $c->get(DateTimeFormatter::class),
            $c->get(HoldExpiryEvaluator::class),
            $c->get(ExpiryCleanupLogger::class),
            $c->pdo()
        ));
        $this->singleton(PaymentHandoffService::class, fn(self $c): PaymentHandoffService => new PaymentHandoffService(
            $c->get(PaymentGatewayStubService::class),
            $c->get(CheckoutRepository::class),
            $c->get(StockReservationService::class),
            $c->get(PlannerService::class),
            $c->pdo()
        ));
        $this->singleton(TicketDeliveryService::class, fn(self $c): TicketDeliveryService => new TicketDeliveryService(
            $c->get(Mailer::class),
            $c->get(TicketPdfService::class),
            $c->get(InvoicePdfService::class)
        ));
        $this->singleton(TicketDeliveryOrchestrator::class, fn(self $c): TicketDeliveryOrchestrator => new TicketDeliveryOrchestrator(
            $c->get(TicketDeliveryService::class),
            $c->get(EventRepository::class),
            $c->get(DateTimeFormatter::class)
        ));
        $this->singleton(CheckoutService::class, fn(self $c): CheckoutService => new CheckoutService(
            $c->pdo(),
            $c->get(PlannerService::class),
            $c->get(CheckoutRepository::class),
            $c->get(CheckoutHoldManager::class),
            $c->get(HoldExpiryEvaluator::class),
            $c->get(DateTimeFormatter::class),
            $c->get(CheckoutValidationService::class),
            $c->get(StockReservationService::class),
            $c->get(PaymentHandoffService::class),
            $c->get(TicketDeliveryOrchestrator::class)
        ));
        $this->singleton(PasswordResetService::class, fn(self $c): PasswordResetService => new PasswordResetService(
            $c->get(UserRepository::class),
            $c->get(Mailer::class),
            $c->get(AuthService::class)
        ));
        $this->singleton(PageService::class, fn(self $c): PageService => new PageService(
            $c->get(PageRepository::class)
        ));
        $this->singleton(EventService::class, fn(self $c): EventService => new EventService(
            $c->get(EventRepository::class),
            $c->get(PageRepository::class)
        ));
        $this->singleton(ContentService::class, fn(self $c): ContentService => new ContentService(
            $c->get(PageRepository::class)
        ));
        $this->singleton(LocationService::class, fn(self $c): LocationService => new LocationService(
            $c->get(LocationRepository::class)
        ));

        $this->transient(HomeController::class, static fn(self $c): HomeController => new HomeController());
        $this->transient(PageController::class, fn(self $c): PageController => new PageController(
            $c->get(PageService::class),
            $c->get(EventService::class),
            $c->get(LocationService::class),
            $c->get(PlannerService::class),
        ));
        $this->transient(CheckoutController::class, fn(self $c): CheckoutController => new CheckoutController(
            $c->get(PlannerService::class),
            $c->get(CheckoutService::class),
            $c->get(AuthService::class),
            $c->get(UserRepository::class),
            $c->get(CheckoutRepository::class)
        ));
        $this->transient(PlannerController::class, fn(self $c): PlannerController => new PlannerController(
            $c->get(PlannerService::class)
        ));
        $this->transient(AuthController::class, fn(self $c): AuthController => new AuthController(
            $c->get(UserRepository::class),
            $c->get(AuthService::class),
            $c->get(CaptchaService::class)
        ));
        $this->transient(AccountController::class, fn(self $c): AccountController => new AccountController(
            $c->get(UserRepository::class),
            $c->get(AuthService::class)
        ));
        $this->transient(OrdersController::class, fn(self $c): OrdersController => new OrdersController(
            $c->get(AuthService::class),
            $c->get(UserRepository::class),
            $c->get(OrderRepository::class)
        ));
        $this->transient(PasswordController::class, fn(self $c): PasswordController => new PasswordController(
            $c->get(PasswordResetService::class)
        ));
        $this->transient(CmsController::class, fn(self $c): CmsController => new CmsController(
            $c->get(PageService::class),
            $c->get(ContentService::class),
            $c->get(EventService::class),
            $c->get(LocationService::class),
        ));
    }

    private function pdo(): PDO
    {
        return $this->get(PDO::class);
    }
}
