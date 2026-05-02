# Plan: Targeted Code Review Fixes — Auth, Planner, Checkout

**Project root:** `/Users/joedavtian/HaarlemFest/app`
**Scope guardrail:** Auth, Planner, Checkout flows ONLY.
**Out of scope (do NOT touch):** `PageController`, `CmsController`, `OrderRepository`, any CMS/Page views.
**Architecture rules:** Controller -> Service -> Repository -> Database. Service interfaces required. Business validation in Service, input validation in Controller. Repository returns Models, Service processes Models, Controller converts to ViewModel.
**Course constraints:** No result wrapper objects. No `match`. Prepared statements. `htmlspecialchars()` in views. Simple patterns.

---

## Context

Eight discrete review fixes. All decisions are final — executor must not re-open them. Fixes touch a single service trio (TicketDelivery + Checkout), two controllers (Account, Checkout, Planner), one service (Planner), and remove one model + one empty directory.

Phase ordering is driven by one hard dependency: `DeliveryResult` model can only be deleted AFTER both `TicketDeliveryService` and `CheckoutService` stop referencing it.

---

## Work Objectives

1. Simplify ticket delivery to a single void method that emails ticket + invoice PDFs together.
2. Replace course-disallowed `match` in `AccountController` with `if/elseif/else`.
3. Harden checkout POST handling against array values.
4. Unify `PlannerService` item shape to a single array form.
5. Replace loose comparison with strict comparison in planner.
6. Normalize `PlannerController` indentation and remove dead code.
7. Delete obsolete `DeliveryResult` model and the empty `Services/Results/` directory.

---

## Guardrails

**Must Have**
- Every modified Service still has its interface, controllers depend on the interface.
- All views remain `htmlspecialchars()`-correct (no view changes expected).
- Order placement remains successful even if email sending fails (silent catch).
- Behavior of `AccountController` branch (Fix 2) is byte-equivalent to current `match`.
- 4-space indentation consistent across `PlannerController.php`.

**Must NOT Have**
- No edits to `PageController`, `CmsController`, `OrderRepository`, CMS/Page views.
- No new result-wrapper objects, no `match` expressions, no service-layer HTTP/HTML.
- No business logic moved into controllers; no DB access added to controllers/services.
- No change to `Mailer`, `TicketPdfService`, `InvoicePdfService`, `PdfTextSanitizer`.
- No changes to other services' return types or signatures.

---

## Task Flow (Phased, Dependency-Ordered)

```
Phase 1: Update TicketDeliveryService + interface (new void method, both PDFs in one mail)
Phase 2: Update CheckoutService to call new method (drop DeliveryResult usage)
Phase 3: Delete DeliveryResult model
Phase 4: AccountController match -> if/elseif/else
Phase 5: CheckoutController $_POST whitelist
Phase 6: PlannerService item shape unification + strict comparison
Phase 7: PlannerController indentation + dead code removal
Phase 8: Delete empty Services/Results/ directory
Phase 9: Verification sweep (greps + smoke check)
```

Phases 4–8 are independent of 1–3 and could be parallelized, but executor should run sequentially for clean diffs.

---

## Detailed TODOs

### Phase 1 — TicketDeliveryService rewrite (Fix 1, part A)

**Files:**
- `app/src/Services/Interfaces/ITicketDeliveryService.php`
- `app/src/Services/TicketDeliveryService.php`

**Steps:**
1. Rewrite `ITicketDeliveryService` to declare exactly one method:
   ```php
   public function sendOrderConfirmation(User $user, int $orderId, array $tickets, float $total): void;
   ```
2. Rewrite `TicketDeliveryService` per the brief:
   - Constructor injects `Mailer`, `TicketPdfService`, `InvoicePdfService` only.
   - Single public method `sendOrderConfirmation(...)`.
   - Generate ticket PDF, generate invoice PDF, build a simple HTML body, send ONE email with both PDFs attached via `Mailer`.
   - Wrap entire body in `try { ... } catch (\Throwable $e) { /* swallow or error_log */ }`.
   - Return `void`.
   - Remove `DeliveryResult` import and any reference to it.
3. Remove all previous public methods (`deliverPurchaseEmails`, etc.) and any helpers solely used by them.

**Acceptance:**
- `grep -n "DeliveryResult" app/src/Services/TicketDeliveryService.php` returns nothing.
- `grep -nE "public function" app/src/Services/TicketDeliveryService.php` shows exactly two: `__construct` and `sendOrderConfirmation`.
- Interface contains only `sendOrderConfirmation` (plus PHP open tag/namespace/use/imports).
- File line count drops substantially below 350.

---

### Phase 2 — CheckoutService migration (Fix 1, part B)

**File:** `app/src/Services/CheckoutService.php`

**Steps:**
1. Locate the call site that invokes the old `deliverPurchaseEmails(...)` and reads from `DeliveryResult` (e.g. `$delivery->emailWarning()`).
2. Replace with `$this->ticketDeliveryService->sendOrderConfirmation($user, $orderId, $tickets, $total);`.
3. Delete any local variable that captured the old `DeliveryResult`.
4. Remove any code path that propagates an `emailWarning` to the controller/ViewModel.
5. Remove `use App\Models\DeliveryResult;` import.

**Acceptance:**
- `grep -rn "DeliveryResult" app/src/Services/CheckoutService.php` returns nothing.
- `grep -n "deliverPurchaseEmails" app/src/Services/CheckoutService.php` returns nothing.
- `grep -n "sendOrderConfirmation" app/src/Services/CheckoutService.php` returns exactly one call.
- Checkout still compiles (PHP `php -l` clean).

---

### Phase 3 — Delete DeliveryResult model (Fix 1, part C)

**File:** `app/src/Models/DeliveryResult.php`

**Steps:**
1. Confirm zero remaining references project-wide:
   `grep -rn "DeliveryResult" app/src` should return nothing.
2. Delete `app/src/Models/DeliveryResult.php`.
3. Remove any stale `use App\Models\DeliveryResult;` line still lingering anywhere (defensive `grep` after delete).

**Acceptance:**
- File no longer exists.
- `grep -rn "DeliveryResult" app/` returns nothing.

---

### Phase 4 — AccountController: replace `match` (Fix 2)

**File:** `app/src/Controllers/AccountController.php` (around lines 149–157)

**Steps:**
1. Read the existing `match` block.
2. Translate each arm to an `if` / `elseif` / `else` chain producing the same value(s) and side effects.
3. Preserve variable names, return points, and default branch behavior exactly.

**Acceptance:**
- `grep -n "match[[:space:]]*(" app/src/Controllers/AccountController.php` returns nothing.
- Manual diff shows only syntactic translation; no behavioral changes.
- `php -l app/src/Controllers/AccountController.php` clean.

---

### Phase 5 — CheckoutController: $_POST whitelist (Fix 3)

**File:** `app/src/Controllers/CheckoutController.php` (~line 30)

**Steps:**
1. Confirm a `REQUIRED_FIELDS` class constant exists; if not, add a `private const REQUIRED_FIELDS = [...]` listing the form fields the checkout actually consumes (use the existing list — do not invent fields).
2. Replace:
   ```php
   $details = array_map('trim', $_POST);
   ```
   with:
   ```php
   $details = [];
   foreach (self::REQUIRED_FIELDS as $field) {
       $details[$field] = trim((string) ($_POST[$field] ?? ''));
   }
   ```
3. Leave downstream usage of `$details` untouched.

**Acceptance:**
- `grep -n "array_map('trim'" app/src/Controllers/CheckoutController.php` returns nothing.
- `grep -n "REQUIRED_FIELDS" app/src/Controllers/CheckoutController.php` returns the const definition + the foreach.
- Posting an array value (e.g. `name[]=a&name[]=b`) no longer triggers a PHP 8 warning (behavioral check).

---

### Phase 6 — PlannerService: unify item shape + strict compare (Fix 4 + Fix 5)

**File:** `app/src/Services/PlannerService.php`

**Steps:**
1. In `addItem(...)`:
   - Always store `['quantity' => (int) $quantity, 'familyTicket' => (bool) $familyTicket]`.
   - Remove the scalar-quantity branch.
   - Replace `== 'on'` with `=== 'on'` at line ~52 when normalizing the family-ticket flag from POST.
2. In every reader (`itemQuantity()`, `filterOutFreeItems()`, and any other method that inspects an item):
   - Drop the `is_array($item)` / `is_int($item)` branching.
   - Treat every stored item as the array shape unconditionally.
3. If a method increments quantity, ensure it merges into the array shape (preserve existing `familyTicket`).

**Acceptance:**
- `grep -nE "is_int|is_array" app/src/Services/PlannerService.php` returns nothing referencing items.
- `grep -n "== 'on'" app/src/Services/PlannerService.php` returns nothing.
- `grep -n "=== 'on'" app/src/Services/PlannerService.php` returns at least one line.
- All public method signatures unchanged.
- Adding a ticket with and without family flag still produces the expected planner total (behavioral check).

---

### Phase 7 — PlannerController: indentation + dead code (Fix 6 + Fix 7)

**File:** `app/src/Controllers/PlannerController.php`

**Steps:**
1. Convert all leading tabs to 4 spaces throughout the entire file. Preserve relative indentation depth.
2. Remove the `normalizeViewData()` method entirely.
3. Remove any `is_object($data) ? $data->toArray() : $data` (or equivalent) defensive branch — `PlannerService::getDetailedPlanner()` is typed `: array`.
4. Re-run a visual scan for stray tabs (including inside heredocs/nowdocs if any).

**Acceptance:**
- `grep -Pn "^\t" app/src/Controllers/PlannerController.php` returns nothing.
- `grep -n "normalizeViewData" app/src/Controllers/PlannerController.php` returns nothing.
- `grep -n "normalizeViewData" app/` returns nothing (no callers).
- `php -l app/src/Controllers/PlannerController.php` clean.

---

### Phase 8 — Delete empty Services/Results/ directory (Fix 8)

**Path:** `app/src/Services/Results/`

**Steps:**
1. Confirm directory is empty (`ls -la app/src/Services/Results/`).
2. `rmdir app/src/Services/Results/`.

**Acceptance:**
- `test -d app/src/Services/Results && echo exists || echo gone` prints `gone`.

---

### Phase 9 — Verification sweep

**Steps:**
1. `php -l` on every modified PHP file.
2. Project-wide grep:
   - `grep -rn "DeliveryResult" app/` -> empty
   - `grep -rn "deliverPurchaseEmails" app/` -> empty
   - `grep -rn "match[[:space:]]*(" app/src/Controllers/AccountController.php` -> empty
   - `grep -rn "array_map('trim', \$_POST)" app/` -> empty
   - `grep -rn "normalizeViewData" app/` -> empty
3. Smoke flow (manual or scripted): register/login (Auth), add tickets to planner with and without family flag (Planner), complete a checkout (Checkout) — confirm order persists, confirmation email attempt fires (or silently fails) without breaking the redirect.

---

## Success Criteria

- All 8 fixes applied as specified; no decisions re-opened.
- No file outside Auth/Planner/Checkout scope is modified.
- `php -l` clean on every changed file.
- All grep acceptance checks pass.
- Order placement still succeeds end-to-end; email failure does not block users.
- `DeliveryResult.php` and `Services/Results/` no longer exist.
- `PlannerService` stores items in a single, uniform array shape; readers contain no shape-branching.
- `PlannerController` is tab-free and contains no dead `normalizeViewData`.

---

## RALPLAN-DR Summary

**Mode:** SHORT (low-risk, mechanical fixes; no architectural redesign).

### Principles
1. Course-conformance first: no `match`, no result wrappers, simple patterns.
2. Layering integrity: never widen a controller's responsibility or let a service touch HTTP/HTML.
3. Smallest viable diff: behavioral parity unless the brief explicitly changes behavior.
4. Fail-soft on email: order success must not depend on SMTP success.
5. Dependency-ordered deletion: remove a type only after all readers and writers stop referencing it.

### Decision Drivers (top 3)
1. **Course constraints** (Web Dev 1) — disallow `match` and result wrappers; constrain shape of acceptable code.
2. **Layering rules** in `.claude/CLAUDE.md` — controllers thin, services own logic, interfaces required.
3. **Scope discipline** — touching CMS/Page would break the guardrail; out-of-scope code stays untouched even if tempting.

### Options Considered (per major fix, with bounded pros/cons)

**Fix 1 — Email delivery model**
- Option A (chosen): Single void `sendOrderConfirmation`, both PDFs in one mail, swallow exceptions.
  - Pros: removes wrapper, simpler downstream, decouples order success from email.
  - Cons: caller can't surface an "email warning"; user only learns via missing inbox.
- Option B: Keep two emails but drop the wrapper, return bool.
  - Pros: preserves current UX (separate ticket vs invoice mails).
  - Cons: violates the brief; bool is still a thin result wrapper in spirit; doubles SMTP load.
- Option C: Throw on failure, let controller catch.
  - Pros: surfaces errors loudly.
  - Cons: violates brief ("must not block user"); pushes infra concerns into controller.
- Invalidation: B and C contradict the final decisions in the brief.

**Fix 3 — POST sanitization**
- Option A (chosen): Whitelist via `REQUIRED_FIELDS` constant + foreach.
  - Pros: PHP 8-safe, explicit, mirrors course style.
  - Cons: must keep constant in sync with the form.
- Option B: `array_walk_recursive` + type guard.
  - Pros: no constant to maintain.
  - Cons: silently accepts unexpected fields; more complex; still needs guard.
- Invalidation: B is course-disallowed complexity and weakens input validation.

**Fix 4 — Planner item shape**
- Option A (chosen): Always-array shape `['quantity' => N, 'familyTicket' => bool]`.
  - Pros: single readers, no branching, easy to extend.
  - Cons: tiny memory overhead vs scalar.
- Option B: Always-scalar quantity, store `familyTicket` in a parallel map.
  - Pros: smallest payload.
  - Cons: two structures to keep in sync; more bug surface; contradicts brief.
- Invalidation: B contradicts the final decision and increases coupling.

**Fix 2 / 5 / 6 / 7 / 8** — mechanical translations / deletions; no viable alternatives worth enumerating beyond "do it" vs "do nothing." Documented for completeness:
- Fix 2: `match` -> `if/elseif/else` is the only course-conformant translation.
- Fix 5: `==` -> `===` is the only correct strict-equality fix.
- Fix 6: tabs -> 4 spaces matches repo convention; mixed indentation is not an option.
- Fix 7: dead code removal has no alternative worth keeping.
- Fix 8: empty directory has no use; deletion is the only sensible action.

---

## Notes for Executor

- Run `php -l` after every file change.
- Stage commits per phase to keep diffs reviewable.
- If `REQUIRED_FIELDS` does not yet exist in `CheckoutController`, derive it from the actual form view in the checkout flow — do not invent field names.
- Do not refactor `Mailer`, `TicketPdfService`, `InvoicePdfService`, or `PdfTextSanitizer` even if tempted while editing `TicketDeliveryService`.
