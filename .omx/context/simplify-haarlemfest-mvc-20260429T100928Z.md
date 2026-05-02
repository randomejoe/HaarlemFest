# Context Snapshot: Simplify HaarlemFest MVC

## Task Statement
Run Ralph on the supplied `simplify-haarlemfest-mvc.md` plan: simplify the PHP MVC checkout flow by deleting hold/payment-handoff/idempotency infrastructure and replacing checkout with a direct invoice/ticket transaction.

## Desired Outcome
The app keeps the course-relevant MVC shape: controllers call services, services use repositories, repositories use PDO prepared statements. Checkout confirms planner tickets by inserting one row into `invoices`, inserting ticket rows into `tickets`, decrementing event stock in a transaction, preserving CSRF and existing public routes except pending checkout routes.

## Known Facts/Evidence
- PHPUnit baseline before edits: `vendor/bin/phpunit` in `app/` passed with 8 tests and 37 assertions.
- Source root is `app/`, not repository root `src/`.
- The supplied plan says `invoices` remains the persistence table and `/orders` depends on it.
- `InvoicePdfService`, `TicketPdfService`, `PdfTextSanitizer`, `TicketDeliveryService`, `ITicketDeliveryService`, `DeliveryResult`, `CaptchaService`, `Mailer`, and password reset wiring survive.

## Constraints
- No new dependencies.
- Keep CSRF end to end.
- Do not rename `invoices` or `tickets`.
- `findEventForUpdate` must use `SELECT ... FOR UPDATE`.
- Do not sweep `declare(strict_types=1)` or `final class`.
- Delete or simplify only the files listed by the plan and stale references to them.

## Unknowns/Open Questions
- Whether all controller tests should be rewritten or deleted after simplification depends on how small the resulting seam remains. Prefer minimal behavior coverage if practical.
- Manual browser/database checkout scenario may not be feasible without a running database and known credentials; PHPUnit/static checks remain required.

## Likely Codebase Touchpoints
- `app/src/Services/CheckoutService.php`
- `app/src/Controllers/CheckoutController.php`
- `app/src/Repositories/CheckoutRepository.php`
- `app/src/Repositories/Interfaces/ICheckoutRepository.php`
- `app/src/Services/Interfaces/ICheckoutService.php`
- `app/src/Services/PlannerService.php`
- `app/src/Services/Interfaces/IPlannerService.php`
- `app/src/Services/SessionManager.php`
- `app/src/Services/Interfaces/ISessionManager.php`
- `app/src/Models/PlannerSummary.php`
- `app/src/ViewModels/CheckoutViewModel.php`
- `app/src/Views/checkout.php`
- `app/public/index.php`
- `app/tests/*`
