# Deep Interview Spec: Planner / Checkout Flow Rebuild

## Metadata
- Interview ID: planner-checkout-2026-04-21
- Rounds: 7
- Final Ambiguity Score: 16.5%
- Type: brownfield
- Generated: 2026-04-21
- Threshold: 20%
- Status: PASSED

## Clarity Breakdown
| Dimension | Score | Weight | Weighted |
|-----------|-------|--------|----------|
| Goal Clarity | 0.90 | 35% | 0.315 |
| Constraint Clarity | 0.85 | 25% | 0.213 |
| Success Criteria | 0.75 | 25% | 0.188 |
| Context Clarity | 0.80 | 15% | 0.120 |
| **Total Clarity** | | | **0.836** |
| **Ambiguity** | | | **16.5%** |

## Goal
Rebuild the PHP backend (all controllers, services, repositories, models, and viewmodels) for the planner and checkout flow from scratch, strictly following the MVC + Service + Repository rulebook. The existing database schema and frontend views are kept as-is and serve as the fixed contract.

## Constraints
- Keep the existing database schema (7 tables: events, checkout_attempts, checkout_attempt_items, ticket_holds, invoices, tickets, users)
- Keep all existing PHP views (planner.php, checkout.php, checkout_pending.php, orders.php and partials)
- Keep the existing FastRoute routing structure in index.php
- No DI container — wire dependencies manually in bootstrap/index.php
- Every Service must have an interface (IFeatureService)
- Every Repository must have an interface (IFeatureRepository)
- Controllers depend on service interfaces only
- Services depend on repository interfaces only
- Architecture strictly follows: Controller → Service → Repository → Database

## Non-Goals (explicitly out of scope)
- Family ticket pricing (€60/4 tickets — the flag, the pricing logic, the DB column)
- Order history page (/orders, OrdersController, OrderRepository)
- DI container introduction
- Changes to the database schema
- Changes to the frontend views
- PDF email delivery redesign (keep existing Mailer/PDF services as-is, wire them correctly)

## Features Required (Acceptance Criteria)

### Planner
- [ ] `POST /planner/items` — add an event to the session planner (validate: not free, not sold out, stock available)
- [ ] `GET /planner` — display planner items with quantity, unit price, line total, and total summary
- [ ] Time conflict detection — planner warns when two added events overlap in time
- [ ] `POST /planner/items/{eventId}/quantity` — update quantity for an event
- [ ] `POST /planner/items/{eventId}/remove` — remove an event from the planner
- [ ] `POST /planner/clear` — clear all planner items
- [ ] Planner is session-backed (planner_token + $_SESSION['planner'])

### Checkout
- [ ] `GET /checkout` — show checkout page; redirect to login if unauthenticated; prompt to fill user details if any are missing
- [ ] `POST /checkout/details` — save user details (first_name, last_name, address, city, country, phone_number) to users table
- [ ] `POST /checkout/confirm` — initiate checkout: validate idempotency key, reserve stock, create checkout_attempt + items + ticket_holds (10-min expiry), lock planner, hand off to payment stub
- [ ] Idempotency key prevents duplicate submissions
- [ ] If stock is unavailable for any item, return out-of-stock error (do not create attempt)

### Payment & Holds
- [ ] `GET /checkout/pending/{id}` — show pending page with attempt details
- [ ] `POST /checkout/pending/{id}/confirm` — confirm payment: validate hold not expired, create invoice + tickets (one row per ticket with verification_code), mark holds paid, unlock planner
- [ ] Expired holds (>10 min) are released: ticket_holds marked released, events.ticket_amount restored, checkout_attempt marked expired
- [ ] Hold expiry cleanup runs with a 60-second cooldown (not on every request)

### Post-Payment
- [ ] After payment confirmed: generate and send ticket PDF email to user
- [ ] After payment confirmed: generate and send invoice PDF email to user

## Layer Responsibilities (per Rulebook)

### Controllers (thin — request in, service call, view/response out)
- `PlannerController` — delegates all logic to `IPlannerService`
- `CheckoutController` — delegates all logic to `ICheckoutService`

### Services (all business logic and flow control)
- `IPlannerService` / `PlannerService` — manages session planner state, validates events, detects conflicts
- `ICheckoutService` / `CheckoutService` — orchestrates checkout: validates, reserves stock, creates attempt, holds, triggers payment
- `IStockReservationService` / `StockReservationService` — increments/decrements event stock
- `IHoldManager` / `CheckoutHoldManager` — creates, releases, expires ticket holds
- `IPaymentHandoffService` / `PaymentHandoffService` — calls payment stub, returns redirect URL
- `ITicketDeliveryService` / `TicketDeliveryService` — generates PDFs and sends emails post-payment

### Repositories (DB access only — no business logic)
- `IEventRepository` / `EventRepository` — event lookup, stock decrement/increment
- `ICheckoutRepository` / `CheckoutRepository` — checkout_attempts, attempt_items, invoices, tickets CRUD
- `ITicketHoldRepository` / `TicketHoldRepository` — ticket_holds CRUD
- `IUserRepository` / `UserRepository` — user lookup and details update

### Models (data structures, no logic)
- `PlannerItem` — one planner basket entry (eventId, quantity, price, conflicts)
- `PlannerSummary` — aggregated planner (items, total, conflicts, lock status)
- `CheckoutItem` — validated line item for checkout
- `Event` — event data
- `User` — user account data

### ViewModels (UI-shaped data, not persisted)
- `PlannerViewModel` — shaped for planner.php
- `CheckoutViewModel` — shaped for checkout.php
- `PendingViewModel` — shaped for checkout_pending.php

## Technical Context (Brownfield)

### Existing code that is REPLACED by this rebuild
- `app/src/Controllers/PlannerController.php`
- `app/src/Controllers/CheckoutController.php`
- `app/src/Services/PlannerService.php`
- `app/src/Services/CheckoutService.php`
- `app/src/Services/SessionManager.php`
- `app/src/Services/CheckoutHoldManager.php`
- `app/src/Services/StockReservationService.php`
- `app/src/Services/PaymentHandoffService.php`
- `app/src/Services/TicketDeliveryOrchestrator.php`
- `app/src/Services/CheckoutValidationService.php`
- `app/src/Services/CheckoutAttemptStateMachine.php`
- `app/src/Services/HoldExpiryEvaluator.php`
- `app/src/Services/ExpiryCleanupLogger.php`
- `app/src/Repositories/CheckoutRepository.php`
- `app/src/Repositories/TicketHoldRepository.php`
- `app/src/Repositories/EventRepository.php`
- `app/src/Models/PlannerItem.php`
- `app/src/Models/PlannerSummary.php`
- `app/src/Models/CheckoutItem.php`
- `app/src/Services/Results/CheckoutResult.php`
- `app/src/Services/Results/PaymentConfirmationResult.php`
- `app/src/Services/Results/HoldExpiryResult.php`
- `app/src/Services/Results/StockReservationFailure.php`

### Existing code that is KEPT (not touched)
- All views: `app/src/Views/planner.php`, `checkout.php`, `checkout_pending.php`, and partials
- Database schema: all 7 tables and their columns
- `app/public/index.php` routing (may need wiring updates only)
- `app/src/Services/TicketPdfService.php`
- `app/src/Services/InvoicePdfService.php`
- `app/src/Services/Mailer.php`
- `app/src/Services/DateTimeFormatter.php`
- Auth-related code

### Naming Convention (per Rulebook Rule 12)
```
app/src/
  Controllers/
    PlannerController.php
    CheckoutController.php
  Services/
    Interfaces/
      IPlannerService.php
      ICheckoutService.php
      IStockReservationService.php
      ICheckoutHoldManager.php
      IPaymentHandoffService.php
      ITicketDeliveryService.php
    PlannerService.php
    CheckoutService.php
    StockReservationService.php
    CheckoutHoldManager.php
    PaymentHandoffService.php
    TicketDeliveryService.php
    SessionManager.php
  Repositories/
    Interfaces/
      IEventRepository.php
      ICheckoutRepository.php
      ITicketHoldRepository.php
      IUserRepository.php
    EventRepository.php
    CheckoutRepository.php
    TicketHoldRepository.php
    UserRepository.php
  Models/
    PlannerItem.php
    PlannerSummary.php
    CheckoutItem.php
    Event.php
    User.php
  ViewModels/
    PlannerViewModel.php
    CheckoutViewModel.php
    PendingViewModel.php
```

## Interview Transcript
<details>
<summary>Full Q&A (7 rounds)</summary>

### Round 1
**Q:** What do you want to do with the planner/checkout flow?
**A:** Build it from scratch
**Ambiguity:** 71.5%

### Round 2
**Q:** What exactly are you rebuilding (DB, PHP, views)?
**A:** Backend only (PHP) — keep DB schema and views
**Ambiguity:** 49%

### Round 3
**Q:** Which features must the rebuild support?
**A:** Decide per feature
**Ambiguity:** ~45%

### Round 4
**Q:** Must the rebuild include the stock hold/expiry mechanism?
**A:** Yes — keep holds
**Ambiguity:** ~42%

### Round 5
**Q:** Does the rebuilt checkout need to send ticket and invoice PDFs by email?
**A:** Yes — required
**Ambiguity:** ~38%

### Round 6
**Q:** Which remaining features must be included?
**A:** Time conflict detection only (family tickets and order history excluded)
**Ambiguity:** ~23%

### Round 7
**Q:** How should dependency injection be handled?
**A:** Manual wiring — no DI container; interfaces defined per rulebook
**Ambiguity:** 16.5%

</details>
