# Targeted Review Fixes: Auth, Planner, Checkout

## Task Statement
Apply eight code review fixes in the HaarlemFest app, limited to Auth, Planner, and Checkout flows.

## Desired Outcome
- Ticket delivery exposes one void `sendOrderConfirmation()` method and sends ticket plus invoice PDFs in a single email.
- Checkout no longer depends on `DeliveryResult`.
- Account controller has no `match`.
- Checkout POST handling whitelists required scalar fields.
- Planner session items use one array shape: `['quantity' => int, 'familyTicket' => bool]`.
- Planner controller is tab-free and has no `normalizeViewData()` dead code.
- `DeliveryResult.php` and empty `Services/Results/` are removed.

## Known Facts / Evidence
- `TicketDeliveryService` currently imports and returns `DeliveryResult` through `deliverPurchaseEmails()`.
- `CheckoutService` calls `deliverPurchaseEmails()` and logs `emailWarning()`.
- `AccountController::validateProfileLengths()` uses `match`.
- `CheckoutController::saveDetails()` uses `array_map('trim', $_POST)`.
- `PlannerService` stores planner items as either scalars or arrays and uses `== 'on'`.
- `PlannerController` uses leading tabs and calls `normalizeViewData()`.
- Worktree already contains unrelated modifications; preserve them.

## Constraints
- Scope guardrail: Auth, Planner, Checkout flows only.
- Do not touch PageController, CmsController, OrderRepository, CMS/Page views.
- Architecture: Controller -> Service -> Repository -> Database; service interfaces required.
- No result wrapper objects, no `match`, prepared statements, simple course patterns.
- Business validation stays in services; input validation stays in controllers.
- Order placement must still succeed if email sending fails.

## Unknowns / Open Questions
- Full end-to-end browser smoke may depend on local DB/app setup.
- Existing unrelated worktree changes may affect broader test suite results.

## Likely Codebase Touchpoints
- `app/src/Services/Interfaces/ITicketDeliveryService.php`
- `app/src/Services/TicketDeliveryService.php`
- `app/src/Services/CheckoutService.php`
- `app/src/Controllers/AccountController.php`
- `app/src/Controllers/CheckoutController.php`
- `app/src/Services/PlannerService.php`
- `app/src/Controllers/PlannerController.php`
- `app/src/Models/DeliveryResult.php`
- `app/src/Services/Results/`
- `app/tests/Services/CheckoutServiceTest.php`
