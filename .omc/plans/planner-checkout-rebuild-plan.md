# Planner / Checkout Flow Rebuild — Revised Plan (Iteration 2)

**Spec source:** `/Users/joedavtian/HaarlemFest/.omc/specs/deep-interview-planner-checkout.md`
**Rulebook:** `/Users/joedavtian/HaarlemFest/.claude/CLAUDE.md`
**Project root:** `/Users/joedavtian/HaarlemFest`
**Mode:** Ralplan Consensus (Deliberate — high-risk: money flow + schema contract)
**Iteration:** 2 (both Architect and Critic returned ITERATE)
**Output artifact:** `.omc/plans/planner-checkout-rebuild-plan.md` (this file, persisted)

---

## 1. Executive Summary

Rebuild the PHP backend of the planner + checkout flow from scratch, strictly following the rulebook (Controller → Service → Repository, interfaces per layer, no DI container, manual wiring in `app/public/index.php`).

Keep the database schema, views, routing, auth, and PDF/Mailer services untouched. Replace all listed controllers, services, repositories, models, and result objects listed in the spec.

**Core architectural shift from Iteration 1:**
1. Delete `app/src/Container.php` entirely; wire everything in `index.php`.
2. Controllers depend on service interfaces **only** (zero repository imports).
3. Transaction ownership lives solely in `CheckoutService` — inner collaborators accept a `PDO` and never manage TX state.
4. Payment handoff moved **outside** the checkout transaction (split into TX1 → handoff → TX2).
5. `SessionManager` hidden behind `ISessionManager`.
6. `StockReservationService` elevated to a real coordinating service (2 repos, no facade).
7. Domain objects (`CheckoutAttempt`, `Ticket`, `Invoice`) replace raw arrays crossing service boundaries for email delivery.
8. Idempotency race guarded by a new `UNIQUE` index on `checkout_attempts.idempotency_key` (schema addition — the only schema change; see §3.4).

---

## 2. RALPLAN-DR Decision Record

### 2.1 Principles (5)
1. **Rulebook compliance first.** Thin controllers, interfaces per service/repo, service→repo direction only, no cross-layer leaks.
2. **Single transaction owner.** Only `CheckoutService` calls `beginTransaction/commit/rollBack`. Inner collaborators receive a `PDO` for statement execution only.
3. **Keep network I/O outside DB transactions.** Payment handoff HTTP call must not run while a transaction is open.
4. **Schema and views are fixed.** The only schema delta allowed is a UNIQUE index required to make idempotency correctness provable.
5. **Explicit wiring over magic.** All object construction happens in `app/public/index.php` as lazy closures; no container, no reflection.

### 2.2 Decision Drivers (top 3)
1. **Consistency between stock counters, holds, attempts, and planner lock.** Any partial failure must leave a recoverable state (either fully reserved OR fully released).
2. **Idempotency of `POST /checkout/confirm` under concurrent tabs.** Two near-simultaneous POSTs must produce exactly one attempt row.
3. **Controllers must be mechanically rule-compliant.** `grep` over controllers must return zero `App\\Repositories\\` imports after the rebuild.

### 2.3 Options Considered

**Option A — Monolithic transaction around the whole `confirmCheckout` (Iteration 1 approach).**
- Pros: Simple read model; one commit or rollback path.
- Cons: Holds a DB transaction across a network call to the payment stub; violates driver #3; long-lived row locks under load; timeouts poison the DB connection. **INVALIDATED.**

**Option B — Two short transactions with handoff in between (CHOSEN).**
- TX1: reserve stock + insert attempt + items + holds (status `initiated`). Commit.
- Outside TX: call `PaymentHandoffService` (network I/O).
- TX2: mark attempt `handoff_created` OR `handoff_failed` (and restore stock on failure). Commit.
- Pros: No network call inside a TX; crash-safe recovery via expired-hold sweeper; explicit state transitions.
- Cons: Requires an `initiated` state to be swept by expiry logic if TX2 never runs (already supported by existing `markExpiredByIds` which accepts `('initiated','handoff_created')`).

**Option C — Event-sourced saga with outbox table.**
- Pros: Bulletproof reconciliation.
- Cons: Significant schema change; spec forbids schema changes beyond what's minimally required. **INVALIDATED by constraint: "No changes to the database schema" (relaxed only for the idempotency UNIQUE index).**

**Chosen: Option B.** See §9 ADR.

### 2.4 Mode
**DELIBERATE.** Money flow, partial-failure recovery, concurrent-tab race — see §8 pre-mortem and §10 verification.

---

## 3. Scope and Contract

### 3.1 What is replaced (delete & rewrite)
Exactly the files listed in the spec §"Existing code that is REPLACED":

**Controllers (2)**
- `app/src/Controllers/PlannerController.php`
- `app/src/Controllers/CheckoutController.php`

**Services (11) + Results directory**
- `app/src/Services/PlannerService.php`
- `app/src/Services/CheckoutService.php`
- `app/src/Services/SessionManager.php` (rewritten — namespace unchanged: `App\Services`)
- `app/src/Services/CheckoutHoldManager.php`
- `app/src/Services/StockReservationService.php`
- `app/src/Services/PaymentHandoffService.php`
- `app/src/Services/TicketDeliveryOrchestrator.php` **(delete; merge into `TicketDeliveryService`)**
- `app/src/Services/TicketDeliveryService.php` (rewritten)
- `app/src/Services/CheckoutValidationService.php` **(delete; inline logic into `CheckoutService`)**
- `app/src/Services/CheckoutAttemptStateMachine.php` **(delete; state is a private `match` in `CheckoutService`)**
- `app/src/Services/HoldExpiryEvaluator.php` **(delete; fold into `CheckoutHoldManager`)**
- `app/src/Services/ExpiryCleanupLogger.php` **(delete; logging becomes a private method in `CheckoutHoldManager`)**
- `app/src/Services/Results/CheckoutResult.php` **(delete; replaced by domain object `CheckoutResult` — see §6)**
- `app/src/Services/Results/PaymentConfirmationResult.php` **(delete; replaced by `PaymentConfirmationResult` domain object)**
- `app/src/Services/Results/HoldExpiryResult.php` **(delete; replaced by `HoldExpiryResult` domain object)**
- `app/src/Services/Results/StockReservationFailure.php` **(delete; replaced by `StockConflict` model)**
- Delete the empty `app/src/Services/Results/` directory after file removal.

**Repositories (3)**
- `app/src/Repositories/CheckoutRepository.php` (rewritten)
- `app/src/Repositories/TicketHoldRepository.php` (rewritten)
- `app/src/Repositories/EventRepository.php` (rewritten — see §3.3 for CMS method preservation)

**Models (3)**
- `app/src/Models/PlannerItem.php` (rewritten)
- `app/src/Models/PlannerSummary.php` (rewritten)
- `app/src/Models/CheckoutItem.php` (rewritten)

**Infrastructure (1)**
- `app/src/Container.php` **DELETE IN FULL.** Every `use App\Container;` line in the codebase removed. Sole wiring location: `app/public/index.php`.

### 3.2 What is kept (zero changes)
- All views under `app/src/Views/**` (planner.php, checkout.php, checkout_pending.php, partials).
- Database schema (tables: `events`, `checkout_attempts`, `checkout_attempt_items`, `ticket_holds`, `invoices`, `tickets`, `users`, plus CMS/auth tables).
- FastRoute route definitions in `index.php` (only the wiring block around them changes).
- `app/src/Services/TicketPdfService.php`
- `app/src/Services/InvoicePdfService.php`
- `app/src/Services/Mailer.php`
- `app/src/Services/DateTimeFormatter.php`
- `app/src/Services/PaymentGatewayStubService.php`
- `app/src/Services/AuthService.php`, `CaptchaService.php`, `CsrfService.php`
- `app/src/Services/PasswordResetService.php`, `PageService.php`, `UserService.php`, `EventService.php`, `ContentService.php`, `LocationService.php`
- **`app/src/Services/OrderService.php`, `app/src/Repositories/OrderRepository.php`, `app/src/Controllers/OrdersController.php` — kept wired and working unchanged.** Order history is explicitly out of scope per spec §Non-Goals. These files are re-wired in `index.php` identically to today (new keyed entries; no code edits).
- Auth controllers, Account/Password/CMS controllers.
- `app/src/Repositories/UserRepository.php` is kept but **given a new `IUserRepository` interface** (ctor consumers updated). The class body itself is not rewritten.

### 3.3 EventRepository — CMS method preservation

`EventRepository` is rewritten, but every existing method must survive the rewrite. Catalog of methods that the CMS/public-site code calls and that the rewrite **must preserve** (verbatim signatures):

- `findByCategory(string $category): array`
- `findById(int $eventId): ?Event`
- `findByName(string $eventName): array`
- `findVenuesByArtist(string $artistName): array`
- `findByIds(array $eventIds): array`
- `findStockByIds(array $eventIds): array`
- `decrementTicketAmountIfAvailable(int $eventId, int $quantity): bool`
- `incrementTicketAmount(int $eventId, int $quantity): void`
- `getAllEvents(): array`
- `getAllEventsInCategory(string $category): array`
- `createSubEvent(string $category, array $postData)`
- `getEventForEdit(int $id): Event`
- `updateEvent(int $id, array $postData)`
- `deleteEvent(int $id)`

The new `IEventRepository` interface declares only the subset used by the planner/checkout flow (`findById`, `findByIds`, `findStockByIds`, `decrementTicketAmountIfAvailable`, `incrementTicketAmount`). The CMS-side methods stay as public methods on the concrete `EventRepository` class but are **not** part of the interface (they are consumed directly by `EventService` / CMS code, which continues to type-hint the concrete class as it does today — no changes to CMS code required).

**Acceptance check:** `grep -n "findByCategory\|findByName\|findVenuesByArtist\|getAllEvents\|getAllEventsInCategory\|createSubEvent\|getEventForEdit\|updateEvent\|deleteEvent" app/src/Repositories/EventRepository.php` returns all 9 CMS methods after rewrite.

### 3.4 The single schema delta (idempotency UNIQUE index)

The spec says "no changes to the database schema." The only amendment is a **UNIQUE INDEX**, which does not alter any column — it is a correctness guard required to make the concurrent-tab race provable rather than probabilistic.

```sql
ALTER TABLE checkout_attempts
  ADD UNIQUE INDEX uq_checkout_attempts_idempotency_key (idempotency_key);
```

If an existing row already violates uniqueness (possible from pre-rebuild data), a one-shot dedup migration runs first:
```sql
DELETE ca1 FROM checkout_attempts ca1
INNER JOIN checkout_attempts ca2
  ON ca1.idempotency_key = ca2.idempotency_key
 AND ca1.checkout_attempt_id > ca2.checkout_attempt_id
WHERE ca1.status IN ('initiated', 'handoff_failed', 'expired');
```

Delivered as a migration file `app/migrations/2026_04_21_add_idempotency_unique.sql`, applied as part of Step 1. If the operator vetoes even this, the fallback is documented in §8 scenario (d) — but the plan strongly recommends applying it.

---

## 4. Target Directory Layout

```
app/src/
  Controllers/
    PlannerController.php                     (rewritten)
    CheckoutController.php                    (rewritten, 2-dep ctor)
  Services/
    Interfaces/
      IPlannerService.php                     NEW
      ICheckoutService.php                    NEW
      IStockReservationService.php            NEW
      ICheckoutHoldManager.php                NEW
      IPaymentHandoffService.php              NEW
      ITicketDeliveryService.php              NEW
      ISessionManager.php                     NEW
    PlannerService.php                        (rewritten, ctor: IEventRepository + ISessionManager)
    CheckoutService.php                       (rewritten, SOLE TX owner)
    StockReservationService.php               (rewritten, coordinates IEventRepository + ITicketHoldRepository)
    CheckoutHoldManager.php                   (rewritten, accepts PDO, no TX management)
    PaymentHandoffService.php                 (rewritten, stateless; never touches DB)
    TicketDeliveryService.php                 (rewritten, consumes domain objects)
    SessionManager.php                        (rewritten, implements ISessionManager)
  Repositories/
    Interfaces/
      IEventRepository.php                    NEW (planner/checkout subset)
      ICheckoutRepository.php                 NEW
      ITicketHoldRepository.php               NEW
      IUserRepository.php                     NEW
    EventRepository.php                       (rewritten, implements IEventRepository + retains CMS methods)
    CheckoutRepository.php                    (rewritten, implements ICheckoutRepository)
    TicketHoldRepository.php                  (rewritten, implements ITicketHoldRepository)
    UserRepository.php                        (unchanged body; add `implements IUserRepository`)
  Models/
    PlannerItem.php                           (rewritten)
    PlannerSummary.php                        (rewritten)
    CheckoutItem.php                          (rewritten)
    CheckoutAttempt.php                       NEW
    Ticket.php                                NEW
    Invoice.php                               NEW
    StockConflict.php                         NEW
    DeliveryResult.php                        NEW
    CheckoutResult.php                        NEW  (moved from Services/Results/)
    PaymentConfirmationResult.php             NEW  (moved from Services/Results/)
    HoldExpiryResult.php                      NEW  (moved from Services/Results/)
    HoldExpiryReason.php                      NEW  (enum: RELEASED, COOLDOWN, SKIPPED)
    CheckoutAttemptStatus.php                 NEW  (enum: INITIATED, HANDOFF_CREATED, HANDOFF_FAILED, PAID, EXPIRED)
    Event.php                                 (unchanged)
    User.php                                  (unchanged)
  ViewModels/
    PlannerViewModel.php                      NEW
    CheckoutViewModel.php                     NEW
    PendingViewModel.php                      NEW
```

---

## 5. Complete Model Catalog

Per Critic demand, every model used across the planner/checkout flow listed with its role:

| Model | Type | Role | Persistence |
|---|---|---|---|
| `PlannerItem` | Data | One planner line (event + qty + price + conflict flags) | Session |
| `PlannerSummary` | Aggregate | Full planner (items, total, conflicts, lock) | Session-derived |
| `CheckoutItem` | Data | Validated line item crossing into checkout | Transient |
| `CheckoutAttempt` | Domain | `checkout_attempts` row hydrated | DB |
| `Ticket` | Domain | `tickets` row hydrated | DB |
| `Invoice` | Domain | `invoices` row hydrated | DB |
| `StockConflict` | Data | `(eventId, name, requested, available)` | Transient |
| `DeliveryResult` | Data | `(success, message, emailWarning)` from `TicketDeliveryService` | Transient |
| `Event` | Domain | `events` row hydrated (unchanged) | DB |
| `User` | Domain | `users` row hydrated (unchanged) | DB |
| `CheckoutResult` | Data | `(status, message, attemptId?, redirectUrl?, conflicts?)` | Transient |
| `PaymentConfirmationResult` | Data | `(status, message, invoiceId?, emailWarning?)` | Transient |
| `HoldExpiryResult` | Data | `(releasedCount, expiredAttemptIds, ran, reason)` | Transient |
| `CheckoutAttemptStatus` | Enum | `initiated\|handoff_created\|handoff_failed\|paid\|expired` | DB string column |
| `HoldExpiryReason` | Enum | `ran\|cooldown\|skipped` | Transient |

---

## 6. Interface Contracts (Authoritative Signatures)

### 6.1 `ISessionManager`
```php
namespace App\Services\Interfaces;

interface ISessionManager {
    public function getPlannerToken(): string;
    public function getPlannerState(): array;           // associative
    public function setPlannerState(array $planner): void;
    public function setFlash(string $type, string $message): void;
    public function consumeFlash(): ?array;              // ['type','message']|null
    public function shouldRunExpiryCleanup(int $cooldownSeconds): bool;
    public function markExpiryCleanupRun(?int $timestamp = null): void;
    public function resetExpiryCleanupRun(): void;
    public function generateToken(): string;
}
```
`SessionManager` stays in `App\Services` namespace, `implements ISessionManager`.

### 6.2 `IPlannerService`
```php
interface IPlannerService {
    public function getPlannerToken(): string;
    public function getDetailedPlanner(): PlannerSummary;       // returns domain object (view adapter uses toArray())
    public function isLocked(): bool;
    public function getLockedCheckoutAttemptId(): ?int;
    public function lock(int $attemptId, ?string $holdExpiresAt = null): void;
    public function unlock(): void;
    public function unlockIfAttemptId(int $attemptId): bool;
    public function unlockIfExpired(array $expiredAttemptIds): bool;
    public function addItem(int $eventId, int $quantity, ?string $familyTicket): void;
    public function addItems(array $eventIds, int $quantity): int;
    public function updateItemQuantity(int $eventId, int $quantity): void;
    public function removeItem(int $eventId): void;
    public function clear(): void;
    public function getIdempotencyKey(): string;
    public function rotateIdempotencyKey(): string;
    public function setFlash(string $type, string $message): void;
    public function consumeFlash(): ?array;
}
```
Ctor: `(IEventRepository $events, ISessionManager $session)`.

### 6.3 `IStockReservationService` — real coordinating service

```php
interface IStockReservationService {
    /**
     * Atomically reserve stock for every CheckoutItem using the caller's PDO.
     * Caller owns the transaction. Returns a result object; never throws for
     * stock-unavailability (that's a domain outcome, not an exception).
     *
     * @param CheckoutItem[] $checkoutItems
     */
    public function reserveStockForItems(array $checkoutItems, \PDO $pdo): StockReservationResult;

    /**
     * Restore stock for all holds associated with the attempt, and mark the holds
     * released with the given reason. Caller owns the transaction.
     */
    public function restoreStockForAttempt(int $attemptId, string $reason, \PDO $pdo): void;

    /**
     * Non-transactional lookup used to build user-facing conflict messages.
     *
     * @param  array<array{event_id:int,quantity:int,name?:string}> $items
     * @return StockConflict[]
     */
    public function getStockConflicts(array $items): array;
}
```
`StockReservationResult` is a `readonly class` with `bool $ok`, `int[] $failedEventIds`. Coordinates `IEventRepository` and `ITicketHoldRepository`. **Does not call `beginTransaction`** — that is the caller's job. The `$pdo` parameter is the exact same PDO the caller started the TX on; it is used to execute the UPDATE/INSERT statements.

### 6.4 `ICheckoutHoldManager`
```php
interface ICheckoutHoldManager {
    public function createHoldsForAttempt(
        int $attemptId,
        int $userId,
        string $plannerToken,
        array $attemptItems,
        string $expiresAt,
        \PDO $pdo                        // NEW: caller-owned TX
    ): void;

    public function releaseExpiredHolds(): HoldExpiryResult;   // owns its own short TX
    public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult;
    public function markHoldsAsTransferred(int $attemptId, \PDO $pdo): void;
    public function markHoldsAsPaid(int $attemptId, ?string $paidAt, \PDO $pdo): void;
    public function isHoldPastGracePeriod(string $holdExpiresAt): bool;
}
```
**Note:** `releaseExpiredHolds()` and `releaseExpiredHoldsIfNeeded()` are the only methods that start their own transaction, because they run outside any checkout flow (background sweeper). This is the exception and is whitelisted in the transaction contract (§7.2).

### 6.5 `IPaymentHandoffService`
```php
interface IPaymentHandoffService {
    /**
     * Stateless network call to the payment stub. Does NOT touch the DB.
     * Returns a plain result object the caller uses to decide TX2 branching.
     */
    public function initiatePaymentHandoff(
        int $attemptId,
        int $userId,
        string $plannerToken,
        float $amount,
        string $currency,
        string $holdExpiresAt
    ): PaymentHandoffResponse;
}
```
`PaymentHandoffResponse` fields: `bool $success`, `?string $providerReference`, `?string $redirectUrl`, `?string $errorCode`, `?string $errorMessage`. **No PDO, no Repository, no PlannerService — totally stateless.** This is what breaks the Iteration 1 "network inside TX" failure mode.

### 6.6 `ITicketDeliveryService` — domain objects only
```php
interface ITicketDeliveryService {
    /**
     * @param Ticket[] $tickets
     */
    public function deliverPurchaseEmails(
        User $user,
        CheckoutAttempt $attempt,
        array $tickets,
        Invoice $invoice
    ): DeliveryResult;
}
```
No raw arrays. The concrete class consumes `TicketPdfService`, `InvoicePdfService`, `Mailer`, `DateTimeFormatter`.

### 6.7 `ICheckoutService` — the behavior-complete service for the controller
```php
interface ICheckoutService {
    // Planner-lock state (exposed via service, NOT directly via IPlannerService)
    public function isPlannerLocked(): bool;
    public function getLockedAttemptId(): ?int;
    public function unlockIfAttemptId(int $attemptId): void;
    public function clearPlannerIfUnlocked(): void;

    // Session helpers routed through the service layer
    public function consumeFlash(): ?array;
    public function setFlash(string $type, string $message): void;
    public function getIdempotencyKey(): string;

    // View models
    public function buildCheckoutView(User $user): CheckoutViewModel;
    public function buildPendingView(int $attemptId, User $user): PendingViewModel;

    // User details
    public function loadCheckoutUser(int $userId): ?User;
    public function missingCheckoutDetails(User $user): array;
    public function saveCheckoutDetails(int $userId, array $details): void;

    // Hold lifecycle
    public function releaseExpiredHoldsIfNeeded(bool $force = false): HoldExpiryResult;

    // Core flow
    public function confirmCheckout(User $user, string $postedIdempotencyKey): CheckoutResult;
    public function confirmPendingPayment(int $checkoutAttemptId, User $user): PaymentConfirmationResult;
}
```

### 6.8 `ICheckoutRepository` (planner/checkout subset)
Methods: `findById`, `findByIdForUpdate`, `findByIdempotencyKey`, `createAttempt`, `createAttemptItems`, `markHandoffCreated`, `markHandoffFailed`, `markPaid`, `markExpiredByIds`, `findItemsWithEventData`, `findItemsByAttemptId`, `createInvoice`, `createTicketsForAttempt`, `findInvoiceById` (NEW — needed to hydrate `Invoice` domain object), `findTicketsByInvoiceId` (NEW — same).

### 6.9 `ITicketHoldRepository`
Methods: `createHolds`, `findExpiredHoldsForUpdate`, `findByAttemptForUpdate`, `markReleasedByIds`, `markTransferredByAttemptId`, `markPaidByAttemptId`.

### 6.10 `IEventRepository` (planner/checkout subset — CMS methods excluded)
Methods: `findById`, `findByIds`, `findStockByIds`, `decrementTicketAmountIfAvailable`, `incrementTicketAmount`.

### 6.11 `IUserRepository`
Methods: `findById`, `updateCheckoutDetails`, plus whatever existing methods auth/account code already calls on `UserRepository` (preserved as-is; interface declares only what the checkout flow needs).

---

## 7. Transaction Ownership Contract (Blocker-level)

### 7.1 The rule
> Only `CheckoutService::confirmCheckout` and `CheckoutService::confirmPendingPayment` may call `PDO::beginTransaction`, `PDO::commit`, or `PDO::rollBack` inside the checkout/planner flow.

**Exception (whitelisted):** `CheckoutHoldManager::releaseExpiredHolds()` owns its own short sweeper transaction because it runs outside any checkout flow. This is the **only** inner collaborator allowed to begin a TX, and only inside that one method.

`out_of_stock` is a **return value**, never an inner rollback. If `StockReservationService::reserveStockForItems` determines stock is unavailable, it returns `StockReservationResult { ok: false, failedEventIds: [...] }`. The caller (`CheckoutService::confirmCheckout`) inspects the result and decides to `rollBack` (its own TX) and return the `out_of_stock` `CheckoutResult`.

### 7.2 `CheckoutService::confirmCheckout` — split flow

```
[Pre-check — no TX]
  1. If planner locked → return 'locked' CheckoutResult.
  2. Validate idempotency key format → if invalid, return 'invalid_request'.
  3. Look up existing attempt by idempotency key → if found, resolve state and return.
  4. Build CheckoutItem[] from planner summary → if planner empty, return 'empty_planner'.
  5. Missing required user fields → return 'details_required'.

[TX1 — stock + attempt + items + initiated holds]
  beginTransaction($pdo)
  try {
    $stockResult = $stock->reserveStockForItems($items, $pdo);
    if (!$stockResult->ok) {
      rollBack($pdo);
      return CheckoutResult::outOfStock($stock->getStockConflicts($items));
    }
    $attemptId = $checkoutRepo->createAttempt([...status='initiated']);
    $checkoutRepo->createAttemptItems($attemptId, $itemArrays);
    $holdManager->createHoldsForAttempt($attemptId, ..., $pdo);
    commit($pdo);
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) rollBack($pdo);
    if ($e is unique_violation on idempotency_key) {
      // Concurrent tab won; re-read and resolve
      return $this->resolveExistingAttempt($checkoutRepo->findByIdempotencyKey($key));
    }
    throw;
  }

[Network — NO TX open]
  $handoff = $handoffService->initiatePaymentHandoff($attemptId, ..., $holdExpiresAt);

[TX2 — mark result]
  beginTransaction($pdo)
  try {
    if ($handoff->success) {
      $checkoutRepo->markHandoffCreated($attemptId, 'stub_provider', $handoff->providerReference);
      $holdManager->markHoldsAsTransferred($attemptId, $pdo);
      commit($pdo);
      $planner->lock($attemptId, $holdExpiresAt);
      $planner->rotateIdempotencyKey();
      return CheckoutResult::handoffCreated($attemptId, $handoff->redirectUrl);
    } else {
      $stock->restoreStockForAttempt($attemptId, 'handoff_failed', $pdo);
      $checkoutRepo->markHandoffFailed($attemptId, $handoff->errorCode, $handoff->errorMessage);
      commit($pdo);
      $planner->rotateIdempotencyKey();
      return CheckoutResult::handoffFailed($handoff->errorMessage);
    }
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) rollBack($pdo);
    // Leave attempt in 'initiated' state; sweeper will release in <= 60s + 10min.
    // Return a 'retry_later' CheckoutResult.
    return CheckoutResult::retryLater();
  }
```

### 7.3 `CheckoutService::confirmPendingPayment` — single TX (unchanged structure)

Network-free; keeps one transaction. Email sending (network) happens **after** the commit returns.

```
beginTransaction($pdo)
try {
  $attempt = $checkoutRepo->findByIdForUpdate($id);    // row lock
  // ... state checks (not_found, forbidden, expired, already_paid, etc.)
  $invoiceId = $checkoutRepo->createInvoice(...);
  $tickets = $checkoutRepo->createTicketsForAttempt(...);
  $holdManager->markHoldsAsPaid($id, $now, $pdo);
  $checkoutRepo->markPaid($id);
  commit($pdo);
} catch (Throwable $e) { rollBack($pdo); throw; }

// After commit — hydrate domain objects and send emails outside TX.
$attemptDomain = CheckoutAttempt::hydrate(...);
$invoiceDomain = Invoice::hydrate(...);
$ticketsDomain = array_map(fn($t) => Ticket::hydrate($t), $tickets);
$delivery = $ticketDelivery->deliverPurchaseEmails($user, $attemptDomain, $ticketsDomain, $invoiceDomain);
return PaymentConfirmationResult::paid($invoiceId, $delivery->emailWarning);
```

### 7.4 Grep-based acceptance for transaction contract
```bash
# Every TX call must live in CheckoutService or the whitelisted sweeper.
grep -rn "beginTransaction\|->commit()\|rollBack" app/src/
# Expected result:
#   app/src/Services/CheckoutService.php  (confirmCheckout + confirmPendingPayment)
#   app/src/Services/CheckoutHoldManager.php  (releaseExpiredHolds ONLY)
# No other matches.
```

---

## 8. Pre-Mortem — 5 Named Failure Scenarios

### (a) Payment handoff timeout
**Scenario:** TX1 commits. `initiatePaymentHandoff` hangs past the HTTP client timeout (say, 30s) and throws.
**Effect:** Attempt is left in `initiated` status with active holds. No TX open during the timeout (driver #3 satisfied).
**Mitigation:**
- `CheckoutService::confirmCheckout` catches the exception from the handoff call; runs TX2 with `handoff_failed` + `restoreStockForAttempt` inside the same request.
- If TX2 itself fails (e.g., DB blip), the sweeper (`CheckoutHoldManager::releaseExpiredHolds`, runs with 60s cooldown + 10-min hold window) will sweep the `initiated` attempt once the hold_expires_at passes. `markExpiredByIds` already accepts `initiated` state.
- User sees a "retry checkout" flash message; idempotency key is rotated only on the success/handoff_failed branches — on timeout we leave the key intact so a retry with the same key hits the `findByIdempotencyKey` short-circuit and resolves to "already_processing".

### (b) Handoff success + TX2 commit failure
**Scenario:** `initiatePaymentHandoff` returns `success=true`. TX2 fails to commit (DB crash, connection dropped).
**Effect:** Payment provider believes a handoff was created, but our DB still shows `status='initiated'`.
**Mitigation:**
- Attempt stays `initiated`; hold expires after 10 min; sweeper releases stock and marks `expired`.
- On the next `/checkout/pending/{id}` visit, the controller sees `expired` and redirects to `/checkout` with a flash.
- The payment stub is idempotent by design (same `checkout_attempt_id` returns the same redirect), so the user's cart retries produce a fresh attempt. For production replacement (non-stub gateway), the plan notes follow-up: a reconciliation job that queries the gateway for orphan handoffs. (§9 Follow-ups.)

### (c) Email delivery failure after `markPaid`
**Scenario:** `markPaid` commits. `deliverPurchaseEmails` throws (Mailer fails, PDF renderer OOM).
**Effect:** User is marked paid, has tickets, but receives no email.
**Mitigation:**
- `deliverPurchaseEmails` returns a `DeliveryResult` with `success=false` and `emailWarning` set. Never throws.
- Controller surfaces the warning via flash: "Payment confirmed — email delivery failed. Contact support with invoice #{id}."
- `/orders` page (kept unchanged) shows the ticket list so the user can screenshot/print.
- Follow-up (§9): resend endpoint.

### (d) Concurrent tabs racing on `/checkout/confirm`
**Scenario:** User opens two tabs, submits both within 50 ms. Both pass the `isLocked` check (session read) before either writes.
**Effect:** Two `INSERT INTO checkout_attempts` statements arrive.
**Mitigation:** `UNIQUE INDEX uq_checkout_attempts_idempotency_key` (§3.4) forces the DB to reject the second insert with error code 23000. `CheckoutService::confirmCheckout` catches `PDOException`, inspects `SQLSTATE`, re-reads by idempotency key, and resolves to `already_pending` (happy path: both tabs land on `/checkout/pending/{id}`).

**Fallback if the UNIQUE index is vetoed:** Use `SELECT ... FOR UPDATE` on a sentinel row (`users.user_id` of the buyer) as a session-level advisory lock at the start of TX1. Documented here only as fallback — the UNIQUE index is strongly preferred.

### (e) Expired hold arriving between stock check and attempt insert
**Scenario:** TX1 passes the `reserveStockForItems` check; before the INSERT lands, a long-running sweeper commits and rolls back stock that we just decremented (stale read).
**Effect:** Impossible with the chosen approach — `decrementTicketAmountIfAvailable` uses a single atomic `UPDATE ... WHERE ticket_amount >= :quantity` that either succeeds or fails. Both statements (reserve + insert) run inside TX1; the sweeper's short TX cannot interleave its commits between them due to MySQL row-level locking on `events.event_id`.
**Mitigation (belt-and-braces):** `createAttempt` and `createHolds` both reference `events.event_id` via foreign key; if the row disappeared mid-TX (it can't, but hypothetically), the FK would fire and the TX aborts. Integration test §10.2 exercises this exact interleaving.

---

## 9. ADR (Architecture Decision Record)

**Decision:** Split `confirmCheckout` into two short DB transactions with the payment handoff HTTP call in between (Option B). Delete `Container.php`; wire in `index.php`. Controllers depend on service interfaces only.

**Drivers:**
1. No network I/O inside a DB transaction.
2. Idempotency of `/checkout/confirm` must be provable under concurrency (UNIQUE index).
3. Controllers must be mechanically rule-compliant (no repo imports, grep-verifiable).

**Alternatives considered:**
- **A (monolithic TX around handoff):** Rejected — violates driver #1.
- **C (event-sourced saga):** Rejected — requires schema changes beyond the minimum allowed.

**Why chosen (Option B):**
- Matches the existing DB state machine (`initiated` → `handoff_created`/`handoff_failed`/`expired`), which already supports the split without schema changes.
- Keeps recovery simple: the expired-hold sweeper handles any attempt abandoned in `initiated`.
- `CheckoutResult` return values make control flow explicit to the controller.

**Consequences:**
- One more DB round-trip per checkout (TX1 commit + TX2 commit) — acceptable given we remove the long-held TX around the network call.
- `PaymentHandoffService` becomes stateless (no DB, no planner) — easier to mock in unit tests.
- `CheckoutService` grows in LoC (takes on state-machine logic that lived in `CheckoutAttemptStateMachine`); mitigated by private `match` expressions for status resolution.

**Follow-ups (not this plan):**
- Payment-gateway reconciliation job for orphan handoffs (pre-mortem scenario b).
- Email resend endpoint (pre-mortem scenario c).
- Structured logging for the sweeper (currently `ExpiryCleanupLogger` is merged into `CheckoutHoldManager` as a private method; a later PR can extract a `LoggerInterface` dependency).

---

## 10. Verification Plan

### 10.1 Unit tests (one per service)
- `PlannerServiceTest` — add/update/remove/clear, lock gating, idempotency key rotation, family-ticket normalization.
- `CheckoutServiceConfirmCheckoutTest` — locked→`locked`, invalid key→`invalid_request`, out_of_stock→`out_of_stock` + no attempt row, happy path→`handoff_created` + planner locked, unique violation→resolved via `findByIdempotencyKey`.
- `CheckoutServiceConfirmPendingPaymentTest` — expired→stock restored, already_paid, forbidden, happy path→invoice+tickets+holds_paid.
- `StockReservationServiceTest` — successful reservation returns `{ok:true}`, partial failure returns `failedEventIds`, `restoreStockForAttempt` increments correctly.
- `CheckoutHoldManagerTest` — create, expire, transfer, paid transitions; sweeper cooldown.
- `PaymentHandoffServiceTest` — success response shape, failure response shape, timeout (exception) surfacing.
- `TicketDeliveryServiceTest` — returns `DeliveryResult` with `success=false` + `emailWarning` when Mailer throws; never re-throws.
- `SessionManagerTest` — planner state round-trip, flash consume, expiry cooldown.
- `CheckoutResultTest`, `PaymentConfirmationResultTest`, `HoldExpiryResultTest` — factory methods + getters.

### 10.2 Integration tests (2)
1. **Happy checkout end-to-end** — POST `/planner/items` → POST `/checkout/confirm` → assert `checkout_attempts.status='handoff_created'`, `ticket_holds.status='transferred'`, `events.ticket_amount` decremented, planner locked. Then POST `/checkout/pending/{id}/confirm` → assert `invoices` row, `tickets` rows (one per quantity), `holds.status='paid'`, planner unlocked.
2. **Expired-hold-at-pending** — Craft an attempt whose `hold_expires_at` is in the past. POST `/checkout/pending/{id}/confirm` → assert `stock restored`, `attempt.status='expired'`, `CheckoutResult.status='expired'`.

### 10.3 E2E smoke flow (1)
Browser-driven script (curl-based acceptable) that walks: add 2 events → checkout → confirm details → pending page loads → confirm payment → success message → `/orders` lists the new tickets.

### 10.4 Grep-based rulebook assertions (run as part of Step 7 acceptance)
```bash
# (1) Controllers must not import repositories
grep -rn "use App\\Repositories\\" app/src/Controllers/
# Expected: zero matches.

# (2) Each service class constructed exactly once (wiring lives only in index.php)
grep -rn "new CheckoutService\|new PlannerService\|new CheckoutHoldManager\|new StockReservationService\|new PaymentHandoffService\|new TicketDeliveryService\|new SessionManager" app/
# Expected: exactly one hit per class, all inside app/public/index.php.

# (3) Transaction ownership
grep -rn "beginTransaction\|->commit()\|rollBack" app/src/
# Expected matches only in CheckoutService.php and CheckoutHoldManager.php::releaseExpiredHolds.

# (4) Container.php fully removed
test ! -f app/src/Container.php && echo "OK: Container removed"
grep -rn "App\\\\Container" app/
# Expected: zero matches.

# (5) Services depend only on interfaces (constructor type hints)
grep -rn "public function __construct" app/src/Services/*.php
# Expected: every ctor param type is either an I* interface, PDO, or a concrete class from the "kept" list (Mailer, TicketPdfService, InvoicePdfService, PaymentGatewayStubService, DateTimeFormatter).

# (6) ITicketDeliveryService signature uses domain objects
grep -n "deliverPurchaseEmails" app/src/Services/Interfaces/ITicketDeliveryService.php
# Expected: parameter types are User, CheckoutAttempt, array, Invoice (not raw arrays).

# (7) EventRepository CMS methods preserved
grep -n "findByCategory\|findByName\|findVenuesByArtist\|getAllEvents\|getAllEventsInCategory\|createSubEvent\|getEventForEdit\|updateEvent\|deleteEvent" app/src/Repositories/EventRepository.php
# Expected: all 9 method names present.
```

---

## 11. Rule 17 (Validation Placement) — Explicit per Route

| Route | Controller responsibility | Service responsibility |
|---|---|---|
| `POST /planner/items` | Parse `event_id` (int), `quantity` (int), `familyTicket` (string\|null), CSRF (already in router) | `PlannerService::addItem` — event existence, not-free, not-sold-out, stock availability, planner-not-locked |
| `POST /planner/items/{id}/quantity` | Parse `{id}` int, `quantity` int; reject negative | `PlannerService::updateItemQuantity` — event in planner, stock, lock |
| `POST /planner/items/{id}/remove` | Parse `{id}` int | `PlannerService::removeItem` — lock check |
| `POST /planner/clear` | None | `PlannerService::clear` — lock check |
| `GET /planner` | None | `PlannerService::getDetailedPlanner` returns `PlannerSummary`; VM adapts |
| `GET /checkout` | Require session user (AuthService) | `ICheckoutService::buildCheckoutView` — hold cleanup, lock redirect, missing-details detection |
| `POST /checkout/details` | Required-field presence (first_name, last_name, address, city, country, phone_number), trim | `ICheckoutService::saveCheckoutDetails` — persist |
| `POST /checkout/confirm` | Require session user; read `idempotency_key` from POST | `ICheckoutService::confirmCheckout` — all state-machine logic, stock, attempt, handoff |
| `GET /checkout/pending/{id}` | Parse `{id}` int; require session user | `ICheckoutService::buildPendingView` — ownership check, status-based unlock, expired redirect |
| `POST /checkout/pending/{id}/confirm` | Parse `{id}` int; require session user | `ICheckoutService::confirmPendingPayment` — row lock, expiry check, invoice/tickets/paid transition, email |

**Rule:** Controllers validate **shape** (exists, is int, not empty). Services validate **meaning** (is this a valid state transition, does the user own this, is stock available).

---

## 12. PageController + Order history wiring (explicit)

Since `Container.php` is deleted, every controller the router dispatches must be constructible from `index.php`. Non-planner/checkout controllers kept unchanged:

- `PageController(PageService, EventService, LocationService, IPlannerService)` — wire in `index.php`. `PlannerService` now implements `IPlannerService`, so the existing `PlannerService`-typed ctor continues to work (no change to `PageController.php` itself required; just its wiring closure).
- `OrdersController(AuthService, UserRepository, OrderRepository)` — wire unchanged.
- `HomeController()`, `AccountController(UserRepository, AuthService)`, `AuthController(UserRepository, AuthService, CaptchaService)`, `PasswordController(PasswordResetService)`, `CmsController(PageService, ContentService, EventService, LocationService, UserService, OrderService)` — wire unchanged.

No code edits in the above. Only new wiring closures in `index.php`.

---

## 13. Step-by-Step Task Flow (6 steps, mapped to acceptance criteria)

### Step 1 — Schema delta + skeleton interfaces + domain models
Deliverables:
- `app/migrations/2026_04_21_add_idempotency_unique.sql` (dedup + UNIQUE index)
- All 7 `I*.php` interface files (empty bodies, authoritative signatures from §6)
- New Models: `CheckoutAttempt`, `Ticket`, `Invoice`, `StockConflict`, `DeliveryResult`
- New Models (ex-Results/): `CheckoutResult`, `PaymentConfirmationResult`, `HoldExpiryResult`
- New Enums: `CheckoutAttemptStatus`, `HoldExpiryReason`
- ViewModels: `PlannerViewModel`, `CheckoutViewModel`, `PendingViewModel`

Acceptance:
- `php -l` passes for every new file.
- Running the migration against a dev DB succeeds.
- No existing code paths are touched (pure additions).

### Step 2 — Rewrite Services + Repositories (implement interfaces)
Deliverables:
- All 7 service implementations rewritten per §6 contracts + §7 TX rules.
- `CheckoutRepository`, `TicketHoldRepository`, `EventRepository` rewritten implementing their interfaces.
- `UserRepository` unchanged body but now `implements IUserRepository`.
- Old `Services/Results/*.php` files deleted; directory removed.
- Deleted classes (per §3.1) fully removed from disk.

Acceptance:
- `php -l` passes everywhere.
- `grep` §10.4 (3) (transaction ownership) passes.
- `grep` §10.4 (7) (EventRepository CMS methods) passes.
- Unit tests from §10.1 pass (services 1–8).

### Step 3 — Rewrite Controllers with 2-dep ctor
Deliverables:
- `PlannerController(IPlannerService $planner)` — thin; CSRF and shape validation only.
- `CheckoutController(ICheckoutService $checkout, AuthService $auth)` — **exactly two dependencies**. All planner state and checkout repo access routed via `ICheckoutService`.

Acceptance:
- `grep "use App\\Repositories\\" app/src/Controllers/CheckoutController.php` returns zero matches (§10.4 (1)).
- `grep "IPlannerService\|PlannerService" app/src/Controllers/CheckoutController.php` returns zero matches (Critic requirement: no `IPlannerService` in ctor).
- Unit tests for each controller action pass by mocking `ICheckoutService`.

### Step 4 — Delete Container.php and rewire index.php
Deliverables:
- `app/src/Container.php` deleted.
- `app/public/index.php` contains:
  - A single `pdo` variable (`Connection::get()`).
  - One `$sessionManager = new SessionManager();` call.
  - Lazy closures (or eager singletons — the plan picks eager for determinism) for every service, repository, and controller listed in §12 plus the planner/checkout classes.
  - The FastRoute dispatcher and CSRF validation block (unchanged semantics; just swap `$container->get(...)` for direct variable references).
- Every `use App\Container;` and `$container->get(...)` reference replaced.

Acceptance:
- `grep -rn "App\\\\Container" app/` returns zero matches (§10.4 (4)).
- Every `new Foo(...)` in §10.4 (2) grep returns exactly one hit per listed class.
- Manual smoke: the site boots, `/`, `/planner`, `/login`, `/cms` all render.

### Step 5 — Integration tests + E2E smoke
Deliverables:
- Integration tests §10.2 (happy flow, expired-at-pending).
- E2E smoke script §10.3 (checked into `app/tests/smoke/checkout_flow.sh` or equivalent).
- Pre-mortem scenarios (d) and (e) have dedicated integration test variants.

Acceptance:
- All integration tests green.
- E2E smoke passes against a seeded local DB.

### Step 6 — Grep assertions + clean-up pass
Deliverables:
- All §10.4 grep commands codified as a single shell script `scripts/verify_rulebook_compliance.sh` that exits non-zero on any violation.
- Orders-history path manually verified: `/orders` still loads; `OrdersController` ctor untouched.
- CMS path manually verified: `/cms/events` still loads; `EventRepository` CMS methods still work.
- Final PR description enumerates every deleted file, every new file, and links to this ADR.

Acceptance:
- `scripts/verify_rulebook_compliance.sh` exits 0.
- Full test suite green.
- Manual QA checklist (below) complete.

---

## 14. Guardrails

### Must Have
- `Container.php` deleted; single wiring point in `index.php`.
- Every service and repository has an interface (`I*`) in `Interfaces/`.
- `CheckoutController` ctor is exactly `(ICheckoutService, AuthService)`.
- Transaction ownership limited to `CheckoutService` (+ whitelisted sweeper).
- Payment handoff outside any DB transaction.
- UNIQUE index on `checkout_attempts.idempotency_key` (or documented fallback).
- All grep assertions in §10.4 pass.

### Must NOT Have
- No DI container / reflection / service locator.
- No repository imports in any controller.
- No `beginTransaction` inside `StockReservationService`, `CheckoutHoldManager::create*`, `PaymentHandoffService`, `TicketDeliveryService`, or any repository.
- No raw-array signatures on `ITicketDeliveryService`.
- No schema changes beyond the idempotency UNIQUE index.
- No changes to views, auth, CMS controllers, PDF/Mailer services, or order-history code.

---

## 15. Manual QA Checklist (run before closing the PR)

1. Add a non-free event to planner via `/planner/items` → appears on `/planner`.
2. Add a second event that overlaps in time → time conflict warning appears.
3. Update quantity → line total recomputes.
4. Visit `/checkout` without login → redirected to `/login?redirect=/checkout`.
5. Visit `/checkout` with incomplete user details → details form shown.
6. Submit details → `/checkout` renders order summary with the idempotency key in a hidden input.
7. Submit `/checkout/confirm` → redirects to `/checkout/pending/{id}`; planner is locked.
8. In a second tab, submit `/checkout/confirm` again (same session) → redirected to same pending page (idempotency resolved).
9. On pending page click confirm → success flash; planner cleared; tickets on `/orders`; email received (or warning flash if Mailer misconfigured).
10. Let a hold expire (10 min) → sweeper marks attempt `expired`; `/checkout` shows retry flash.
11. `/cms/events` still renders and allows edits (EventRepository CMS methods intact).
12. `/orders` still lists previous orders (OrderRepository untouched).

---

## 16. Estimated Complexity: HIGH

Fifteen interfaces, eleven service classes, four repositories, twelve models, two controllers, one wiring rewrite, one schema migration, full test pyramid. Scope is bounded by the spec but the TX contract + grep acceptance make it unforgiving.

---

## 17. Open Questions for Analyst / Reviewer

1. **Migration veto:** If the operator vetoes the UNIQUE index, is the `SELECT ... FOR UPDATE` on `users` fallback acceptable, or should we escalate to a different mechanism (e.g., Redis SETNX)?
2. **Email delivery atomicity:** Is it acceptable that `PaymentConfirmationResult.status='paid'` can be returned with `emailWarning != null`? The plan assumes yes (users see success + warning flash). Confirm.
3. **Sweeper cadence:** Keep the existing 60-second cooldown? The rewrite preserves it, but the cooldown lives in session state — if the user is idle, no request fires the sweeper. Long-term follow-up is a cron-driven sweeper; out of scope for this rebuild.

(These are logged to `.omc/plans/open-questions.md` per planner policy.)

---

**End of Revised Plan — Iteration 2. Ready for Architect + Critic re-review.**
