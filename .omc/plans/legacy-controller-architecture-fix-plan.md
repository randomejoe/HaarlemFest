# Legacy Controller Architecture Fix — Implementation Plan

**Rulebook:** `/Users/joedavtian/HaarlemFest/.claude/CLAUDE.md`
**Project root:** `/Users/joedavtian/HaarlemFest`
**Mode:** Ralplan Consensus (SHORT — low-risk refactor, happy-path behavior parity, no schema change)
**Iteration:** 2 (revised per Architect + Critic feedback)
**Scope:** AuthController, AccountController, OrdersController, PageRepository, ContentService
**Out of scope:** Views, DB schema, auth/login behavior, password reset flow, CMS controllers beyond the Content transaction move, PasswordController, CheckoutService (already on `IUserRepository`).

---

## 1. Executive Summary

Bring three legacy controllers (`AuthController`, `AccountController`, `OrdersController`) and one repository (`PageRepository`) into compliance with the rulebook, matching the pattern the planner/checkout rebuild (Iteration 1) established.

Three violations to eliminate:
1. Controllers importing and calling `UserRepository` / `OrderRepository` directly (Rule 9, Rule 19, Rule 10).
2. Service layer missing interfaces for `AuthService`, `AccountService` (new), and `OrderService`-read-path (Rule 10).
3. Transaction management + input sanitation + `requireAdmin()` inside `PageRepository::updateContentItem()` (Rule 4 — repos do not control application flow or authz).

Strategy: introduce service-layer facades (`IAuthService`, `IAccountService`, `IOrderService`) that wrap `IUserRepository` / `IOrderRepository`; expand `IUserRepository` and add `IOrderRepository` so services can depend on interfaces; move the one transaction + sanitation + authz check out of `PageRepository::updateContentItem` into `ContentService` (the actual caller). Controllers keep the same happy-path external behavior.

### 1.1 Intentional behavior delta (documented)
- `OrdersController` removes the stale-session `findById` → forced-logout defensive branch (see §R4). The new behavior: a logged-in user whose DB row was deleted mid-session sees an empty orders list instead of being force-logged-out from a GET request. This is **intentional, safer than the prior behavior**, and is the ONLY happy-path-observable change in this refactor.

### 1.2 "No behavior change" — scoped claim
For every other flow (login, register, profile update, orders list for a valid user, logout, CMS page edit, CMS content item edit), **there is no happy-path behavior change**. Same HTTP responses, same error messages, same redirects, same DOM.

---

## 2. RALPLAN-DR Decision Record

### 2.1 Principles (5)
1. **Rulebook compliance first.** Controllers import zero `App\Repositories\` classes after this change. Grep is the witness.
2. **No happy-path behavior change.** Same login/register/profile/orders HTTP responses, same error messages, same redirects. The single intentional delta is listed in §1.1.
3. **Interfaces for every service/repository touched.** Per Rule 10, controllers depend on service interfaces; services depend on repository interfaces.
4. **SRP stays intact.** One class = one responsibility; auth (session + password + identity lookup) stays in AuthService; profile ops get their own AccountService; user-order read-path goes into OrderService (existing class, with a known SRP tension accepted in §8).
5. **Minimal surface area.** No new repositories, no DI-container reintroduction, no schema change, no new routes. Add methods to existing services where they fit; only introduce `AccountService` because profile ops have no natural home.

### 2.2 Decision Drivers (top 3)
1. **Mechanical compliance.** `grep "use App\\\\Repositories\\\\" app/src/Controllers/AuthController.php app/src/Controllers/AccountController.php app/src/Controllers/OrdersController.php` must return empty after the fix.
2. **Behavior parity (happy-path).** Existing integration paths (login, register, profile update, orders page, logout, CMS content edit) must produce the exact same HTTP output and side effects as before. No view changes, no route changes.
3. **Testability via interfaces.** `CheckoutServiceTest` already mocks `IUserRepository`; the new services must be mockable the same way, which requires service interfaces (Rule 18).

### 2.3 Options Considered

**Option A — Fold user-orders read path into existing `OrderService` + add profile ops to a new `AccountService` (CHOSEN).**
- Pros: Reuses existing services; no proliferation of micro-services; `OrderService` already exists; fits Rule 12 naming; smallest diff.
- Cons: `OrderService` currently acts only as a CMS facade (its `getForEdit/update/delete` are CMS-shaped). Adding `getOrdersForUser(int)` grows its surface and creates a mild SRP tension (admin-scoped + end-user-scoped in one class). Accepted in §8 Consequences with a named follow-up (`UserOrderService`).
- Subdecision: **profile ops go into a new `AccountService`, NOT `UserService`.** `UserService` is already declared `implements CMSService` and is CMS-scoped (admin delete, getAll for CMS listings). Mixing user-profile-edit (end-user self-service) into that class would violate Rule 11 SRP and would bloat the CMS-facing contract. `AccountService` owns the `/account` flow.

**Option B — Create `UserAccountService` that owns everything user-related (find by id/email/username, create, update profile, reset password).**
- Pros: Single place for all user mutations.
- Cons: Collapses auth, profile, and password-reset into one class; violates SRP (Rule 11); duplicates what `AuthService` and `PasswordResetService` already do; larger blast radius. **INVALIDATED by Rule 11.**

**Option C — Leave `AuthService` as-is (password only) and put user-lookup/create in a new `UserIdentityService`; route AccountController through `UserIdentityService` too.**
- Pros: Keeps `AuthService` narrow.
- Cons: Three services (`AuthService`, `UserIdentityService`, `AccountService`) all needing a `UserRepository` dependency is more wiring than the codebase style warrants (the planner/checkout rebuild kept services broader). Creates confusion about which service owns `findByEmail`. **REJECTED: higher wiring cost without SRP benefit over Option A.**

**Chosen: Option A with the AccountService subdecision.** See §8 ADR.

### 2.4 Mode
**SHORT.** No money flow, no schema change, no concurrency surface, no network I/O. Pure layer-direction refactor with happy-path behavior parity. Pre-mortem + expanded test plan are not required, but §7 verification still enumerates grep-verifiable and runtime-verifiable checks.

---

## 3. Scope and Contract

### 3.1 What changes

**Controllers rewired (3):**
- `app/src/Controllers/AuthController.php` — swap `UserRepository $users` dependency for `IAuthService` (expanded). Remove `use App\Repositories\UserRepository;`. Remove `use App\Exceptions\UserConflictException;` import only if no longer referenced (it still is, for the catch block).
- `app/src/Controllers/AccountController.php` — replace `UserRepository $users` + `AuthService $auth` with `IAccountService $account` + `IAuthService $auth`. Remove `use App\Repositories\UserRepository;`. Length-limit constants move from `UserRepository::*_MAX_LENGTH` to `App\Models\User` class constants (see §3.5).
- `app/src/Controllers/OrdersController.php` — replace `UserRepository $users` + `OrderRepository $orders` with `IAuthService $auth` + `IOrderService $orders`. Remove `use App\Repositories\UserRepository;` and `use App\Repositories\OrderRepository;`. The stale-session `findById` defensive branch is removed (intentional — see §1.1 and §R4).

**Repositories (2 modified + 1 interface added, 0 new concrete):**
- `app/src/Repositories/UserRepository.php` — no behavior change; it already `implements IUserRepository`. `IUserRepository` is the interface that gets expanded. The eight `*_MAX_LENGTH` constants are removed from this class (they live only on `User` — see §3.5 and §M3).
- `app/src/Repositories/OrderRepository.php` — implement a new `IOrderRepository` interface. No behavior change to `findByUserId()` or `getAllOrders()`.
- `app/src/Repositories/PageRepository.php` — `updateContentItem(int $id, array $data)` loses `beginTransaction/commit/rollBack`, the `unset`/`preg_replace` sanitation, the `echo $e`, and the `requireAdmin()` call. The method becomes a pure SQL UPDATE that accepts a pre-encoded JSON string. All stripped responsibilities move to `ContentService` (the single caller — see §3.6).

**Services (3 modified + 1 new + 3 interfaces added/expanded):**
- `app/src/Services/AuthService.php` — add user-lookup and register methods (see §3.3); constructor changes from `()` to `(IUserRepository $users)`. CAPTCHA stays in the controller as input validation per Rule 17 — captcha is a request/anti-bot guard, not business logic.
- `app/src/Services/AccountService.php` — NEW (see §3.3).
- `app/src/Services/OrderService.php` — add `getOrdersForUser(int $userId): array` returning the formatted array currently produced by `OrderRepository::findByUserId`. No changes to CMS-facing methods.
- `app/src/Services/ContentService.php` — **owns the transaction, the sanitation, and the authz guard** for `updateContentItem`. Gains a `private PDO $pdo` second constructor parameter. Introduces a private helper `persistSanitized(int $id, array $data): bool` called from both `update()` and `updateWithImage()`.
- `app/src/Services/PageService.php` — **NO CHANGES.** `PageService::update()` branches to `addContentItemToPage` or `updatePage` and never calls `updateContentItem`. (This corrects Iteration 1's C1 misrouting.)

**New interfaces:**
- `app/src/Services/Interfaces/IAuthService.php`
- `app/src/Services/Interfaces/IAccountService.php`
- `app/src/Services/Interfaces/IOrderService.php`
- `app/src/Repositories/Interfaces/IOrderRepository.php`
- `app/src/Repositories/Interfaces/IUserRepository.php` — **expanded** (not replaced).

**Wiring:**
- `app/public/index.php` — registers `AccountService` singleton; updates `AuthService`, `ContentService`, `OrderService` factories; updates `AuthController`, `AccountController`, `OrdersController` transients to their new dependencies. `PageService` factory is **untouched**.

### 3.2 What does NOT change
- `app/src/Views/**` — zero edits. Confirmed: `grep -rn "MAX_LENGTH" app/src/Views/` returns 0 matches, so the max-length migration needs no view updates and no backward-compat shim on `UserRepository`.
- Database schema — zero migrations.
- `app/src/Services/PageService.php` — zero edits. `PageService::update()` never calls `updateContentItem` (verified: only callers of `updateContentItem` are `ContentService::update()` line 98 and `ContentService::updateWithImage()` line 70).
- `app/src/Controllers/CmsController.php` — no edits. CMS still calls `ContentService::update()` / `ContentService::updateWithImage()` exactly as today (the `CMSService::update(int, array): bool` contract is preserved).
- `app/src/Controllers/PasswordController.php` — no edits (already goes through `PasswordResetService`).
- `app/src/Services/PasswordResetService.php` — no edits; continues to hold its own `UserRepository` dependency. (Deliberate scope boundary; flagged as a follow-up in §8.) However, the `AuthService` constructor signature change in Step 7 DOES affect `PasswordResetService` indirectly through the DI factory — see §R7.
- `app/src/Services/UserService.php` — no edits (CMS-scoped; untouched).
- `app/src/Services/CheckoutService.php` — no edits. Already uses `IUserRepository` (verified: line 16 `use App\Repositories\Interfaces\IUserRepository;` and line 43 `private IUserRepository $users`). Confirms this plan does not need to touch it.
- `app/src/Controllers/PlannerController.php`, `CheckoutController.php`, `PageController.php`, `HomeController.php` — no edits.
- `app/src/Repositories/BaseRepository.php` and its `requireAdmin()` helper — no edits. (Other PageRepository methods keep their `requireAdmin()` calls; only `updateContentItem` loses it because the guard moves up to `ContentService`.)
- Routing in `app/public/index.php`'s `simpleDispatcher` — no edits.
- CSRF flow, session handling, ALTCHA challenge endpoint — no edits.

### 3.3 New/modified service contracts

**`IAuthService`** (new — `app/src/Services/Interfaces/IAuthService.php`)

```php
namespace App\Services\Interfaces;

use App\Models\User;

interface IAuthService
{
    // existing AuthService methods (kept)
    public function login(User $user): void;
    public function syncCurrentUser(User $user): void;
    public function logout(): void;
    public function isLoggedIn(): bool;
    public function currentUser(): ?User;
    public function hashPassword(string $plaintext): string;
    public function verifyPassword(string $plaintext, string $hash): bool;

    // new — absorbs controller's direct repo calls
    public function findByEmail(string $email): ?User;
    public function findByUsername(string $username): ?User;
    public function findByIdentifier(string $identifier): ?User; // email-vs-username routing
    public function registerUser(string $username, string $email, string $plaintextPassword): User; // returns fully-hydrated User with id; throws UserConflictException on duplicate
}
```

`AuthService`'s constructor changes from `()` to `(IUserRepository $users)`. `registerUser` performs:
1. **Preflight `findByEmail` and `findByUsername` lookups** to produce a user-friendly `UserConflictException` matching the current controller behavior.
2. `hashPassword($plaintext)`.
3. `$users->create(username, email, passwordHash)` — the DB unique index is the race-condition backstop; if the preflight races with another concurrent insert, the `UserConflictException` thrown by `UserRepository::create` propagates.
4. Return `new User(id: $newId, username, email, role: UserRole::User)`.

Both the preflight (user-friendly error) and the DB unique constraint (race backstop) are intentionally active — they are not redundant. The `UserConflictException` catch that currently lives in `AuthController` moves to the service (the service throws; the controller catches and renders the error — controller owns the view, Rule 2).

**`IAccountService`** (new — `app/src/Services/Interfaces/IAccountService.php`)

```php
namespace App\Services\Interfaces;

use App\Models\User;

interface IAccountService
{
    public function loadProfile(int $userId): ?User;
    public function updateProfile(int $userId, array $profileData): User; // returns refreshed User; throws UserConflictException on duplicate username
    public function isUsernameTakenByOther(string $username, int $excludingUserId): bool;
}
```

`AccountService`'s constructor: `(IUserRepository $users)`. `updateProfile` performs the uniqueness preflight (`isUsernameTakenByOther` inline), calls `$users->updateProfile($userId, $profileData)` (lets any native `UserConflictException` propagate as a race-condition backstop), then re-fetches via `$users->findById($userId)` and returns it. Throws `RuntimeException` if the re-fetch returns null. The controller's `syncCurrentUser(...)` call stays in the controller (session identity is an auth concern, not a profile concern).

**`IOrderService`** (new — `app/src/Services/Interfaces/IOrderService.php`)

```php
namespace App\Services\Interfaces;

interface IOrderService
{
    /**
     * @return array<int, array<string,mixed>> Order summaries for the given user.
     */
    public function getOrdersForUser(int $userId): array;
}
```

The concrete `OrderService` already exists and implements `CMSService`. It will ALSO implement `IOrderService` (a class may implement multiple interfaces). The method body delegates to `$this->repository->findByUserId($userId)`. Constructor switches from `OrderRepository` to `IOrderRepository`.

**`CMSService` interface contract** (unchanged — reference only):
Verified: `CMSService` declares `getForEdit(int)`, `isNameEditable(): bool`, `update(int, array): bool`, `delete(int): bool`. `OrderService` must keep implementing all four. `ContentService` also implements this interface and already defines all four — the Step 5 changes preserve the `update(int, array): bool` signature.

### 3.4 New/modified repository contracts

**`IUserRepository`** (expanded — `app/src/Repositories/Interfaces/IUserRepository.php`)

```php
namespace App\Repositories\Interfaces;

use App\Models\User;

interface IUserRepository
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByUsername(string $username): ?User;
    public function create(string $username, string $email, string $passwordHash): int;
    public function updateProfile(int $userId, array $profileData): void;
    public function updateCheckoutDetails(int $userId, array $details): void;
}
```

`UserRepository` already implements all six concretely; no body changes required — only the interface grows to match. `setPasswordResetToken`, `findByResetTokenHash`, `updatePassword`, `getAllUsers`, `deleteUser` intentionally stay off the interface (they are used only by `PasswordResetService` / `UserService` and do not need to be mocked from controllers).

**`IOrderRepository`** (new — `app/src/Repositories/Interfaces/IOrderRepository.php`)

```php
namespace App\Repositories\Interfaces;

interface IOrderRepository
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function findByUserId(int $userId): array;

    /**
     * @return array<int, \App\Models\OrderSummary>
     */
    public function getAllOrders(): array;
}
```

`OrderRepository` gains `implements IOrderRepository`. Zero body changes.

### 3.5 Max-length constant migration

The controllers currently read `UserRepository::USERNAME_MAX_LENGTH`, `UserRepository::EMAIL_MAX_LENGTH`, `UserRepository::FIRST_NAME_MAX_LENGTH`, etc. This is a Rule 9 violation in spirit (controllers coupling to a repository class). Move these eight constants from `UserRepository` to `App\Models\User` as `public const`:

```php
public const USERNAME_MAX_LENGTH = 255;
public const EMAIL_MAX_LENGTH = 255;
public const FIRST_NAME_MAX_LENGTH = 100;
public const LAST_NAME_MAX_LENGTH = 100;
public const ADDRESS_MAX_LENGTH = 255;
public const CITY_MAX_LENGTH = 120;
public const COUNTRY_MAX_LENGTH = 120;
public const PHONE_NUMBER_MAX_LENGTH = 40;
```

**Backward-compat aliases on `UserRepository` are NOT added.** Grep confirms `grep -rn "MAX_LENGTH" app/src/Views/` returns 0 matches — no view references these constants. The eight `const` declarations on `UserRepository` are removed entirely; constants live only on `App\Models\User`. (Critic feedback M3.)

### 3.6 PageRepository / ContentService transaction fix

**Current `PageRepository::updateContentItem(int $id, array $data)`** (lines 139-166) does:
- `$this->requireAdmin();`
- `$this->pdo->beginTransaction();`
- `unset($data['name']); unset($data['csrf_token']);`
- foreach field: `preg_replace('/^<[^>]+>|<\/[^>]+>$/', '', ...)`.
- `UPDATE page_content SET data = :data WHERE content_id = :id` with `json_encode($data)`.
- `commit()`; on exception `echo $e; rollback(); return false;`.

**Actual callers** (verified by `grep -n "updateContentItem" app/src/Services/ContentService.php app/src/Services/PageService.php`):
- `app/src/Services/ContentService.php:70` — `return $this->repository->updateContentItem($id, $data);` inside `updateWithImage`.
- `app/src/Services/ContentService.php:98` — `return $this->repository->updateContentItem($id, $data);` inside `update`.
- `PageService` has **zero** matches. It is not a caller.

**New split:**
- `ContentService::persistSanitized(int $id, array $data): bool` (new private helper) — owns the transaction + sanitation + authz guard. Called from both `update()` and `updateWithImage()`.
- `PageRepository::updateContentItem(int $id, string $encodedJson): bool` — single prepared statement; no transaction, no sanitation, no `echo`, no `requireAdmin()`.

**`ContentService::persistSanitized` body:**
```php
private function persistSanitized(int $id, array $data): bool
{
    $this->users->requireAdmin(); // authz guard at the service boundary — Rule 4: repo must not make authz decisions
    unset($data['name'], $data['csrf_token']);
    foreach ($data as $key => $value) {
        $data[$key] = preg_replace('/^<[^>]+>|<\/[^>]+>$/', '', (string) $value);
    }
    try {
        $this->pdo->beginTransaction();
        $ok = $this->repository->updateContentItem($id, json_encode($data));
        $this->pdo->commit();
        return $ok;
    } catch (\Throwable $e) {
        error_log('ContentService::persistSanitized: ' . $e->getMessage());
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        return false;
    }
}
```

**Note on `requireAdmin()`:** The current `PageRepository::updateContentItem` calls `$this->requireAdmin()` at line 141. This authz gate must not disappear; per Rule 4 ("Repositories must NOT make decisions about application flow") it belongs in the service. The simplest preserving move is to call the existing `BaseRepository::requireAdmin()` via an admin-scoped helper. Because `BaseRepository::requireAdmin()` is a protected method on the repo class, `ContentService` cannot invoke it directly. Two options, of which (a) is chosen:

(a) **Invoke the guard through the repository layer while keeping the decision at the service layer.** Add a new public method `PageRepository::assertAdmin(): void` that wraps `$this->requireAdmin()`, and call it from `ContentService::persistSanitized` as the first line. The repo method is a 1-line authz-check passthrough; all flow control (decide whether to guard) still lives in the service. This is a minimal addition that preserves the guard semantics and keeps repos free of conditional logic.

(b) Extract `requireAdmin()` to a new `AuthGuardService`. Heavier; deferred.

**Chosen: (a).** `ContentService::persistSanitized` begins with `$this->repository->assertAdmin();`. `PageRepository::updateContentItem` no longer calls `$this->requireAdmin()` — the guard runs once, at the service boundary, before the transaction opens. (All OTHER `PageRepository` methods that currently call `$this->requireAdmin()` — `createPage`, `getPageForEdit`, `getContentForEdit`, `updatePage`, `addContentItemToPage`, `deletePage`, `deleteContentItem` — are **untouched**. They remain out of scope.)

**`PageRepository::updateContentItem` new body:**
```php
public function updateContentItem(int $id, string $encodedJson): bool
{
    $stmt = $this->pdo->prepare('UPDATE page_content SET data = :data WHERE content_id = :id');
    $stmt->execute(['id' => $id, 'data' => $encodedJson]);
    return true;
}
```

The signature change (from `array $data` to `string $encodedJson`) is deliberate: it makes the contract honest — the repository stores a JSON blob; it does not parse or sanitize it.

---

## 4. Step Ordering (dependency-first)

Execute in this order; each step is independently green-buildable.

### Step 0 — PSR-4 autoloader verification (preflight, no code change expected)
**Why:** New interfaces live under `App\Services\Interfaces\` → `src/Services/Interfaces/` and `App\Repositories\Interfaces\` → `src/Repositories/Interfaces/`. The existing `composer.json` declares only `App\\` → `src/`, which covers both namespaces by PSR-4 subtree resolution. Both directories already exist (populated from Iteration 1), so no autoload changes are required.

**Verify:**
- `cat app/composer.json` — confirm `"App\\": "src/"` entry.
- `ls app/src/Services/Interfaces/ app/src/Repositories/Interfaces/` — confirm both directories exist.
- If and only if autoload maps ever get an explicit per-subpath entry that doesn't cover Interfaces, add them and run `composer dump-autoload`. (Current state: not needed.)

**Acceptance:** This step is a no-op documentation check. Zero files edited.

### Step 1 — Repository interfaces and constant migration (no behavior change)
**Files:**
- NEW: `app/src/Repositories/Interfaces/IOrderRepository.php` (two-method interface per §3.4).
- MODIFY: `app/src/Repositories/Interfaces/IUserRepository.php` (expand from 2 methods to 6 per §3.4).
- MODIFY: `app/src/Repositories/OrderRepository.php` (add `implements IOrderRepository`; no body change).
- MODIFY: `app/src/Models/User.php` (add eight `public const *_MAX_LENGTH` per §3.5).
- MODIFY: `app/src/Repositories/UserRepository.php` (**remove** the eight `*_MAX_LENGTH` constants; no body change to methods).

**Acceptance criteria:**
- `php -l` passes on each changed/new file.
- `grep -n "class UserRepository extends BaseRepository implements IUserRepository" app/src/Repositories/UserRepository.php` matches (interface implementation unchanged).
- `grep -c "public function" app/src/Repositories/Interfaces/IUserRepository.php` returns **6**.
- `grep -n "class OrderRepository" app/src/Repositories/OrderRepository.php` shows `implements IOrderRepository`.
- `grep -n "MAX_LENGTH" app/src/Repositories/UserRepository.php` returns **0 matches**. (Critic M3.)
- `grep -cn "MAX_LENGTH" app/src/Models/User.php` returns **8**.
- Runtime check: `php -r "require 'vendor/autoload.php'; var_dump(App\\Models\\User::USERNAME_MAX_LENGTH);"` echoes `int(255)`.
- Existing tests still compile: `cd app && ./vendor/bin/phpunit 2>&1 | tail`.

### Step 2 — AuthService expansion + IAuthService interface
**Files:**
- NEW: `app/src/Services/Interfaces/IAuthService.php` (twelve-method interface per §3.3).
- MODIFY: `app/src/Services/AuthService.php`:
  - Add `use App\Repositories\Interfaces\IUserRepository; use App\Models\User; use App\Models\UserRole;`.
  - Add `implements IAuthService`.
  - Add `private IUserRepository $users;` and constructor `(IUserRepository $users)`.
  - Add `findByEmail($email)` → `$this->users->findByEmail($email)`.
  - Add `findByUsername($username)` → `$this->users->findByUsername($username)`.
  - Add `findByIdentifier($identifier)` → `str_contains($identifier, '@') ? $this->findByEmail($identifier) : $this->findByUsername($identifier)`.
  - Add `registerUser(string $username, string $email, string $plaintextPassword): User`:
    1. `if ($this->findByEmail($email) !== null || $this->findByUsername($username) !== null) { throw new UserConflictException('That email or username is already in use.'); }` — preflight for user-friendly error (matches current controller behavior).
    2. `$hash = $this->hashPassword($plaintextPassword);`
    3. `$newId = $this->users->create($username, $email, $hash);` — the DB unique constraint re-throws `UserConflictException` on race.
    4. `return new User(id: $newId, username: $username, email: $email, role: UserRole::User);`.

**Acceptance criteria:**
- `php -l app/src/Services/AuthService.php` passes.
- `grep -n "implements IAuthService" app/src/Services/AuthService.php` matches.
- `grep -n "public function registerUser" app/src/Services/AuthService.php` matches.
- Existing `CheckoutServiceTest` still passes (`CheckoutService` is on `IUserRepository` already — unaffected).
- `PasswordResetService` constructor still accepts `AuthService $auth` and still calls `hashPassword` (signature preserved). **Note:** the AuthService constructor change is applied to the DI factory in Step 7 simultaneously — see §R7 for why this matters.

### Step 3 — New AccountService + IAccountService interface
**Files:**
- NEW: `app/src/Services/Interfaces/IAccountService.php` (three-method interface per §3.3).
- NEW: `app/src/Services/AccountService.php`:
  - `namespace App\Services;`
  - `use App\Repositories\Interfaces\IUserRepository; use App\Services\Interfaces\IAccountService; use App\Models\User; use App\Exceptions\UserConflictException;`.
  - `implements IAccountService`.
  - Constructor `(IUserRepository $users)`.
  - `loadProfile(int $userId): ?User` → delegates to `$this->users->findById($userId)`.
  - `isUsernameTakenByOther(string $username, int $excludingUserId): bool` → `$existing = $this->users->findByUsername($username); return $existing !== null && $existing->getId() !== $excludingUserId;`.
  - `updateProfile(int $userId, array $profileData): User`:
    1. If `isset($profileData['username'])` and `isUsernameTakenByOther(...)` → `throw new UserConflictException('Username is already in use.')`.
    2. `$this->users->updateProfile($userId, $profileData)` — lets native `UserConflictException` (race) propagate.
    3. `$refreshed = $this->users->findById($userId); if ($refreshed === null) { throw new \RuntimeException('User disappeared after profile update'); } return $refreshed;`.

**Acceptance criteria:**
- `php -l app/src/Services/AccountService.php` passes.
- `php -l app/src/Services/Interfaces/IAccountService.php` passes.
- `grep -rn "App\\\\Services\\\\AccountService" app/src/` — after Step 7 matches in `AccountService.php`, `index.php`, `AccountController.php` only.

### Step 4 — OrderService expansion + IOrderService interface

**Step 4 pre-check (before adding `implements IOrderService`):**
- `cat app/src/Services/CMSService.php` confirms four methods: `getForEdit(int)`, `isNameEditable(): bool`, `update(int, array): bool`, `delete(int): bool`. **Verified during plan drafting.**
- `php -l app/src/Services/OrderService.php` — must pass before modification. After modification, re-run and confirm all four `CMSService` methods are still implemented by `OrderService`. If `OrderService` does not currently declare `create` (it shouldn't — `create` is not on `CMSService`), no stub is needed. **If `CMSService` is ever expanded with a method `OrderService` does not implement, this step must add the stub to maintain interface satisfaction.**

**Files:**
- NEW: `app/src/Services/Interfaces/IOrderService.php` (one-method interface per §3.3).
- MODIFY: `app/src/Services/OrderService.php`:
  - Add `use App\Repositories\Interfaces\IOrderRepository; use App\Services\Interfaces\IOrderService;`.
  - Change class declaration to `class OrderService implements CMSService, IOrderService`.
  - Swap constructor parameter from `OrderRepository $repository` to `IOrderRepository $repository`. Property type-hint also becomes `IOrderRepository`.
  - Add `public function getOrdersForUser(int $userId): array { return $this->repository->findByUserId($userId); }`.
  - CMS-facing methods (`getAll`, `getForEdit`, `update`, `delete` if present) unchanged.

**Acceptance criteria:**
- `php -l app/src/Services/OrderService.php` passes.
- `grep -n "implements CMSService, IOrderService" app/src/Services/OrderService.php` matches.
- CMS `getAllOrders` path still returns `$this->repository->getAllOrders()` unchanged.

### Step 5 — PageRepository::updateContentItem transaction moves to ContentService
**Files:**
- MODIFY: `app/src/Repositories/PageRepository.php`:
  - Add a new public method `assertAdmin(): void` whose body is `$this->requireAdmin();`. (1-line authz-check passthrough; keeps decision at the service layer.)
  - Change `updateContentItem(int $id, array $data): bool` → `updateContentItem(int $id, string $encodedJson): bool`.
  - Body becomes: single prepared statement `UPDATE page_content SET data = :data WHERE content_id = :id`; execute with `['id' => $id, 'data' => $encodedJson]`; return `true`. No `requireAdmin()`, no try/catch, no transaction, no `echo`, no `preg_replace`, no `unset`.
- MODIFY: `app/src/Services/ContentService.php`:
  - Add `use PDO;`.
  - Add `private PDO $pdo;` property.
  - Change constructor to `public function __construct(PageRepository $repository, PDO $pdo) { $this->repository = $repository; $this->pdo = $pdo; }`.
  - Add private method `persistSanitized(int $id, array $data): bool` per §3.6 body above (calls `$this->repository->assertAdmin();` as first line; then `unset` + `preg_replace`; then `try { beginTransaction; $ok = $this->repository->updateContentItem($id, json_encode($data)); commit; return $ok; } catch (...) { error_log; rollBack; return false; }`).
  - In `update(int $id, array $postData): bool` (line 98): replace `return $this->repository->updateContentItem($id, $data);` with `return $this->persistSanitized($id, $data);`.
  - In `updateWithImage(int $id, array $postData, array $fileData): bool` (line 70): replace `return $this->repository->updateContentItem($id, $data);` with `return $this->persistSanitized($id, $data);`.
- **`app/src/Services/PageService.php` is NOT modified.** Verified: `grep -n "updateContentItem" app/src/Services/PageService.php` returns 0 matches. (Corrects Iteration 1 C1.)

**Acceptance criteria:**
- `grep -n "beginTransaction\\|->commit()\\|rollBack\\|rollback" app/src/Repositories/PageRepository.php` returns **0 matches**.
- `grep -n "echo \\$e\\|print_r" app/src/Repositories/PageRepository.php` returns **0 matches**.
- `grep -n "requireAdmin" app/src/Repositories/PageRepository.php` returns matches **only** in the methods explicitly kept (`createPage`, `getPageForEdit`, `getContentForEdit`, `updatePage`, `addContentItemToPage`, `deletePage`, `deleteContentItem`) plus the new `assertAdmin` — NOT in `updateContentItem`.
- `grep -n "beginTransaction" app/src/Services/ContentService.php` returns **exactly 1 match** (inside `persistSanitized`).
- `grep -n "beginTransaction" app/src/Services/PageService.php` returns **0 matches**. (PageService untouched.)
- `grep -n "public function assertAdmin" app/src/Repositories/PageRepository.php` returns **1 match**.
- `php -l` passes on all three files.
- Manual CMS test: edit a content item in `/cms/pages/{id}/edit` and confirm the updated JSON appears in `page_content.data`; no "echo $e" stray output in response; admin-required 403/redirect still fires for a non-admin user.

### Step 6 — Controller rewires
**Files:**

**`app/src/Controllers/AuthController.php`:**
- Remove `use App\Repositories\UserRepository;`.
- Keep `use App\Exceptions\UserConflictException;` (still used in the `register` catch block).
- Constructor: `(IAuthService $auth, CaptchaService $captcha)`. Drop the `UserRepository` property.
- `register()`:
  - Replace the current `$this->users->findByEmail($email) || $this->users->findByUsername($username)` preflight with:
    ```php
    try {
        $user = $this->auth->registerUser($username, $email, $password);
    } catch (UserConflictException $e) {
        $this->renderRegisterError(...); // same args and message as today
        return;
    }
    ```
  - Replace `$this->auth->login(new User(id: $userId, ...))` with `$this->auth->login($user)` (service already returned the fully-built `User`).
- `login()`: replace the `if (str_contains($identifier, '@')) { $this->users->findByEmail(...); } else { $this->users->findByUsername(...); }` block with `$user = $this->auth->findByIdentifier($identifier);`. Password verification logic is unchanged.
- `validateRegistrationLengths(...)`: switch `UserRepository::USERNAME_MAX_LENGTH` → `User::USERNAME_MAX_LENGTH`, same for email (add `use App\Models\User;` if not present).
- `logout()`, `altchaChallenge()`, `sanitizeRedirect()`, `renderRegisterError()`, `renderLoginError()` — no edits.

**`app/src/Controllers/AccountController.php`:**
- Remove `use App\Repositories\UserRepository;`.
- Add `use App\Services\Interfaces\IAccountService;` and `use App\Services\Interfaces\IAuthService;`.
- Constructor: `(IAccountService $account, IAuthService $auth)`.
- `show()`: replace `$this->users->findById($sessionUser->getId())` with `$this->account->loadProfile($sessionUser->getId())`.
- `update()`: replace the uniqueness check + `updateProfile` + re-fetch block with:
  ```php
  try {
      $updatedUser = $this->account->updateProfile($userId, [... normalized array ...]);
  } catch (UserConflictException $e) {
      $this->renderAccountError($submittedUser, $e->getMessage());
      return;
  }
  $this->auth->syncCurrentUser($updatedUser);
  header('Location: /account?updated=1');
  exit;
  ```
- `validateProfileLengths(...)`: switch `UserRepository::*_MAX_LENGTH` → `User::*_MAX_LENGTH` (seven occurrences).

**`app/src/Controllers/OrdersController.php`:**
- Remove `use App\Repositories\OrderRepository;` and `use App\Repositories\UserRepository;`.
- Add `use App\Services\Interfaces\IAuthService;` and `use App\Services\Interfaces\IOrderService;`.
- Constructor: `(IAuthService $auth, IOrderService $orders)`. Drop `UserRepository` and `OrderRepository` properties.
- `show()`:
  - Remove the `$this->users->findById($sessionUser->getId())` call at line 30 (stale-session defensive branch). **This is the single intentional behavior delta — see §1.1 and §R4.**
  - Replace `$this->orders->findByUserId($sessionUser->getId())` with `$this->orders->getOrdersForUser($sessionUser->getId())`.
  - The top-of-method `currentUser()` null check remains the only login guard (same as other controllers).

**Acceptance criteria (Step 6, cross-controller):**
- `grep -rn "use App\\\\Repositories\\\\" app/src/Controllers/AuthController.php app/src/Controllers/AccountController.php app/src/Controllers/OrdersController.php` returns **0 matches**.
- `grep -rn "UserRepository\\|OrderRepository" app/src/Controllers/AuthController.php app/src/Controllers/AccountController.php app/src/Controllers/OrdersController.php` returns **0 matches** (excluding comments).
- `grep -n "Repository" app/src/Controllers/OrdersController.php` returns **0 matches**.
- `php -l` on all three controllers passes.

### Step 7 — Wiring update in `app/public/index.php`

**Modifications (all in a single commit with Step 2 / Step 5 changes so factory signatures match the service constructors — see §R7):**
- `AuthService` factory: change to `$registerSingleton(AuthService::class, static fn(callable $get): AuthService => new AuthService($get(UserRepository::class)));` (was zero-arg). **Critical: this must land in the same commit as the AuthService constructor change (Step 2), because `PasswordResetService` resolves `AuthService` at runtime — see §R7.**
- `ContentService` factory: change to `$registerSingleton(ContentService::class, static fn(callable $get): ContentService => new ContentService($get(PageRepository::class), $get(PDO::class)));` (was one-arg). Reflects the new second constructor arg from Step 5.
- `PageService` factory: **UNCHANGED.** `PageService` constructor did not change (remains one-arg `PageRepository`).
- ADD singleton: `$registerSingleton(AccountService::class, static fn(callable $get): AccountService => new AccountService($get(UserRepository::class)));` — plus a top-of-file `use App\Services\AccountService;`.
- `OrderService` factory: unchanged at the call site (still passes `$get(OrderRepository::class)`). PHP accepts the concrete `OrderRepository` where the constructor type-hints `IOrderRepository` (concrete implements interface).
- `AuthController` transient: `new AuthController($get(AuthService::class), $get(CaptchaService::class))` (drop `UserRepository`).
- `AccountController` transient: `new AccountController($get(AccountService::class), $get(AuthService::class))`.
- `OrdersController` transient: `new OrdersController($get(AuthService::class), $get(OrderService::class))` (drop `UserRepository` and `OrderRepository`).
- No other factories touched.

**Acceptance criteria:**
- `php -l app/public/index.php` passes.
- Boot the app (`php -S localhost:8080 -t app/public`) and hit `/`, `/login`, `/register`, `/account`, `/orders`, `/cms`, `/password/forgot` — each returns the expected 200/302 with no `RuntimeException: Service 'X' is not registered.` and no `TypeError: AuthService::__construct()` mismatch.
- `grep -n "AccountService::class" app/public/index.php` matches **≥ 2** (registration + consumer).
- `grep -n "new AuthService()" app/public/index.php` returns **0 matches** (the zero-arg form is gone).

### Step 8 — Regression verification
- Run `cd app && ./vendor/bin/phpunit` (or `composer test`). `CheckoutServiceTest` must stay green (no overlap with changed code, but guards against DI regressions).
- Smoke flows (manual, in order):
  1. POST `/register` with a fresh username/email → redirects to `/`; row exists in `users`.
  2. POST `/register` with duplicate email → renders register view with the same error string as before ("That email or username is already in use.").
  3. POST `/login` with email identifier → session populated, redirect to intended URL.
  4. POST `/login` with username identifier → same.
  5. POST `/login` with wrong password → "Invalid credentials." on login view.
  6. GET `/account` while logged in → renders profile with same DOM.
  7. POST `/account` with changed username → redirects to `/account?updated=1`; DB reflects update; session username syncs.
  8. POST `/account` with username already owned by another user → renders account view with "Username is already in use.".
  9. GET `/orders` → renders order list with the same `orders` array shape and contents as before (snapshot: pick one known invoice in the dev DB and verify rendered ticket count + total price match pre-refactor).
  10. POST `/logout` → redirects to `/login`; session destroyed.
  11. CMS: edit a page content item (`/cms/pages/{id}/edit` → content edit flow) → `page_content.data` updated with sanitized JSON; no "echo $e" output in response.
  12. CMS: attempt content edit as a non-admin → same authz redirect/403 as before (authz guard now at ContentService; must fire before the transaction opens).
  13. **POST `/password/forgot` with a valid email** → `PasswordResetService` resolves cleanly (verifies `AuthService` factory change in Step 7 didn't break the dependency chain — §R7 mitigation).

---

## 5. Detailed File Inventory

**New files (5):**
1. `app/src/Repositories/Interfaces/IOrderRepository.php`
2. `app/src/Services/Interfaces/IAuthService.php`
3. `app/src/Services/Interfaces/IAccountService.php`
4. `app/src/Services/Interfaces/IOrderService.php`
5. `app/src/Services/AccountService.php`

**Modified files (9):**
1. `app/src/Repositories/Interfaces/IUserRepository.php` (expand to 6 methods)
2. `app/src/Repositories/OrderRepository.php` (add `implements`)
3. `app/src/Repositories/UserRepository.php` (remove eight `*_MAX_LENGTH` constants)
4. `app/src/Repositories/PageRepository.php` (drop `updateContentItem` transaction/sanitation/authz; add `assertAdmin()`)
5. `app/src/Models/User.php` (add eight `*_MAX_LENGTH` constants)
6. `app/src/Services/AuthService.php` (expand + interface + `IUserRepository` dep)
7. `app/src/Services/OrderService.php` (add `IOrderService` + read-path method + interface-typed dep)
8. `app/src/Services/ContentService.php` (own transaction + sanitation + authz for `updateContentItem`)
9. `app/public/index.php` (wiring)

**Controller rewires (3):**
1. `app/src/Controllers/AuthController.php`
2. `app/src/Controllers/AccountController.php`
3. `app/src/Controllers/OrdersController.php`

**Untouched (explicit):**
- `app/src/Controllers/CmsController.php`, `PageController.php`, `HomeController.php`, `PasswordController.php`, `PlannerController.php`, `CheckoutController.php`.
- `app/src/Services/PageService.php`, `PasswordResetService.php`, `UserService.php`, `CMSService.php`, `CaptchaService.php`, `CheckoutService.php`, all other services.
- Every file under `app/src/Views/`.
- `app/db/migrations/`.
- `app/src/Repositories/BaseRepository.php` (the `requireAdmin()` helper; `assertAdmin()` on `PageRepository` is a 1-line passthrough to the inherited `requireAdmin()`).

---

## 6. Acceptance Criteria (full list, grep-verifiable where possible)

### 6.1 Mechanical rule compliance (runnable checks)
1. `grep -rn "use App\\\\Repositories\\\\" app/src/Controllers/AuthController.php app/src/Controllers/AccountController.php app/src/Controllers/OrdersController.php` → **0 matches**.
2. `grep -rn "UserRepository\\|OrderRepository" app/src/Controllers/AuthController.php app/src/Controllers/AccountController.php app/src/Controllers/OrdersController.php` → **0 matches**.
3. `grep -n "beginTransaction\\|->commit()\\|rollBack\\|rollback" app/src/Repositories/PageRepository.php` → **0 matches**.
4. `grep -n "echo \\$e\\|print_r" app/src/Repositories/PageRepository.php` → **0 matches**.
5. `grep -n "beginTransaction" app/src/Services/ContentService.php` → **exactly 1 match**.
6. `grep -n "beginTransaction" app/src/Services/PageService.php` → **0 matches**.
7. `grep -rn "implements IAuthService\\|implements IAccountService\\|implements IOrderService\\|implements IOrderRepository" app/src/` → **≥ 4 matches** (one per concrete class).
8. `grep -c "public function" app/src/Repositories/Interfaces/IUserRepository.php` → **6**.
9. `grep -n "MAX_LENGTH" app/src/Repositories/UserRepository.php` → **0 matches**.
10. `grep -cn "MAX_LENGTH" app/src/Models/User.php` → **8**.
11. `grep -n "MAX_LENGTH" app/src/Views/` → **0 matches** (sanity — unchanged from pre-refactor).
12. `grep -n "new AuthService()" app/public/index.php` → **0 matches**.
13. `grep -n "public function assertAdmin" app/src/Repositories/PageRepository.php` → **1 match**.
14. `php -l` on every changed/new file exits 0.

### 6.2 Behavior parity (runtime checks)
15. Fresh register → redirect to `/` or the sanitized `redirect` param; new row in `users`; session populated.
16. Duplicate-email register → register view rendered with literal message "That email or username is already in use." (same as before).
17. Login with email → session populated, redirected.
18. Login with username → session populated, redirected.
19. Login with wrong password → "Invalid credentials." on login view.
20. `/account` GET logged-in → renders account view, same DOM as before.
21. `/account` POST with new display values → DB reflects all seven editable fields; redirect `/account?updated=1`; session username syncs.
22. `/account` POST with duplicate username (taken by another user) → error "Username is already in use." on account view.
23. `/orders` GET logged-in → renders orders view with same `orders` array shape and contents as before (snapshot: one known invoice in dev DB; ticket count + total price match pre-refactor).
24. `/logout` POST → redirect `/login`; session destroyed.
25. CMS content-item edit (as admin) → `page_content.data` updated with sanitized JSON; no transaction leaked (check `SHOW PROCESSLIST`).
26. CMS content-item edit (as non-admin) → same 403/redirect as pre-refactor (authz guard now at `ContentService::persistSanitized` via `PageRepository::assertAdmin`).
27. POST `/password/forgot` with valid email → triggers reset flow without DI error (proves §R7 mitigation).

### 6.3 Test suite
28. `cd app && ./vendor/bin/phpunit` is green (`CheckoutServiceTest` must not regress).

### 6.4 Wiring
29. App boots with zero `"Service 'X' is not registered"` and zero `TypeError` on the eight routes exercised in §4 Step 8.

---

## 7. Risk Register and Mitigations

| # | Risk | Likelihood | Impact | Mitigation |
|---|------|------------|--------|------------|
| R1 | `OrderService` dual-interface (`CMSService, IOrderService`) causes name collision | LOW | LOW | Methods are disjoint (`getOrdersForUser` vs `getForEdit/update/delete/getAll`). Verified against `CMSService` contract (`getForEdit/isNameEditable/update/delete`). |
| R2 | Existing view files reference `UserRepository::*_MAX_LENGTH` | NONE | N/A | Grep confirms 0 matches in `app/src/Views/`. Aliases NOT needed; constants removed outright from `UserRepository` (§M3). |
| R3 | `PasswordResetService` still holds a concrete `UserRepository` dependency | LOW | LOW | Explicitly out of scope. Left untouched; flagged as a follow-up in §8. The current violation is controllers→repos, not services→repos (Rule 9 targets the former). |
| R4 | `OrdersController` drops the defensive `findById(sessionUser->getId())` check | LOW | LOW | **Intentional delta** (see §1.1). If a user is logged in but their row was deleted mid-session, `currentUser()` still returns the session-backed User object; `getOrdersForUser` returns empty; view renders an empty orders list. Strictly safer than forced-logout-from-GET. Documented in §8 Consequences. |
| R5 | `IUserRepository` expansion breaks the `CheckoutServiceTest` mock | LOW | LOW | `CheckoutServiceTest` uses `IUserRepository&MockObject`; PHPUnit mocks satisfy whatever methods the interface declares without requiring stubbed returns. Adding four methods the SUT doesn't call is a no-op. Verified in Step 1 acceptance (run the suite). |
| R6 | `ContentService::persistSanitized` introduces a transaction where the caller already holds one | VERY LOW | MEDIUM | `ContentService` is called only from `CmsController`; `CmsController` never opens a transaction. The new code checks `inTransaction()` before `rollBack` to avoid PDO exceptions if the driver is not in a transaction state. |
| R7 | `AuthService` constructor change (`()` → `(IUserRepository $users)`) must ship atomically with the `index.php` factory update | MEDIUM | HIGH | `PasswordResetService` resolves `AuthService` at runtime via the DI closure. If the factory is updated without the class (or vice versa), `PasswordResetService` resolution throws `TypeError`. **Mitigation:** Step 2 (class change) + Step 7 (factory change) land in the same commit. Step 8 smoke test #13 exercises `POST /password/forgot` to verify the chain. |

---

## 8. ADR — Architectural Decision Record

**Decision:** Introduce service-layer interfaces (`IAuthService`, `IAccountService`, `IOrderService`) and expand `IUserRepository` + add `IOrderRepository` so that `AuthController`, `AccountController`, and `OrdersController` depend only on service interfaces. Move the single `PageRepository::updateContentItem` transaction + sanitation + authz guard into `ContentService` (its sole caller).

**Drivers:**
1. Rulebook Rule 9 and Rule 19 violations in three controllers must be eliminated mechanically (grep-verifiable).
2. The planner/checkout rebuild (Iteration 1) already established the interface-per-service pattern; consistency with that pattern is required.
3. `CheckoutServiceTest` already mocks `IUserRepository`; the new services must support the same testing idiom (Rule 18).
4. Rule 4: repositories must not contain application-flow decisions (transactions) or authz decisions (`requireAdmin`). The `updateContentItem` method mixed both.

**Alternatives considered:**
- Option B — single `UserAccountService` owning auth + profile + reset. **Rejected** (Rule 11 SRP).
- Option C — three-service split with a dedicated `UserIdentityService`. **Rejected** (more wiring, no SRP benefit over Option A).

**Why chosen:**
Option A reuses existing classes (`AuthService`, `OrderService`, `ContentService`), adds exactly one new service (`AccountService`) for end-user profile ops (distinct from CMS-scoped `UserService`), and stays within the existing DI style (lazy closures in `index.php`). Smallest diff for complete compliance. The transaction + sanitation + authz move targets the actual caller (`ContentService`), not a hypothetical one (`PageService`).

**Consequences:**
- (+) Controllers have zero repository imports; rule compliance is grep-verifiable.
- (+) `AuthService` becomes the canonical "user identity" seam; `PasswordResetService` could adopt `IAuthService` in a future cleanup.
- (+) Max-length constants live on the domain model (`User`), not the repository — proper layering.
- (+) `PageRepository::updateContentItem` becomes a pure data-access method; the service boundary owns the transaction, sanitation, and authz.
- (−) `AuthService` grows from 8 methods to 12 — still single-responsibility ("user identity and session"), but the class is bigger.
- (−) `OrderService` implements two interfaces (`CMSService, IOrderService`). `OrderService` absorbs a read-only end-user method (`getOrdersForUser`) into a CMS-scoped class. This is a **known SRP tension**, intentionally accepted for minimal diff. If more end-user order methods accrete, extract a separate `UserOrderService` in a future iteration.
- (−) `OrdersController` loses the stale-session forced-logout branch. New behavior: deleted-user session sees empty orders list instead of forced logout. Intentional; safer for normal operations (§1.1, §R4).
- (−) `PageRepository` gains a 1-line `assertAdmin()` passthrough to expose the inherited `BaseRepository::requireAdmin()` to `ContentService`. Minor additional method; keeps the authz *decision* at the service layer while reusing the existing guard implementation.

**Follow-ups (out of scope for this plan):**
1. Migrate `PasswordResetService` to depend on `IUserRepository` (or a new `IAuthService` extension) once stability is confirmed.
2. If end-user order methods accrete in `OrderService`, extract `UserOrderService`.
3. Consider extracting `AuthService`'s session-state concerns into `ISessionManager` (already exists for checkout) if the class grows further.
4. Audit remaining `PageRepository::requireAdmin()` calls across other methods (`createPage`, `getPageForEdit`, `getContentForEdit`, `updatePage`, `addContentItemToPage`, `deletePage`, `deleteContentItem`) — Rule 4 debatable. Not fixed here; flagged.

---

## 9. Out-of-Scope Explicit List

- Views (`app/src/Views/**`) — untouched.
- Database schema + migrations — untouched.
- CMS controllers and their service flows (beyond the `PageRepository::updateContentItem` → `ContentService` move).
- `PageService` — untouched (confirmed not a caller of `updateContentItem`).
- `PasswordController` / `PasswordResetService` internals.
- `UserService` (CMS-scoped).
- `CheckoutService` — already on `IUserRepository` (verified).
- Routing in `index.php`.
- CSRF, ALTCHA, session startup.
- `BaseRepository::requireAdmin()`.
- Other `PageRepository` methods that currently call `requireAdmin()` — intentionally left as-is for this plan.
- Any new unit tests (the existing suite + manual smoke flows suffice for behavior parity; a follow-up plan can add `AccountServiceTest`, `AuthServiceTest`, `ContentServiceTest`).

---

## 10. Open Questions (persisted to `.omc/plans/open-questions.md`)

*(Iteration 1 Open Questions #1 and #2 are resolved and removed: #1 by the C1 correction — `ContentService` is the caller, not `PageService`; #2 by grep confirming zero view references to `UserRepository::*_MAX_LENGTH`, removing the need for backward-compat aliases.)*

1. **Should `OrdersController` retain the stale-session defensive `findById` check?** This plan removes it (R4, §1.1); if the team prefers defensive logout on missing user row, add `IAuthService::refreshUser(int $id): ?User` and restore the pattern. Default here: remove, because force-logging-out from a GET is worse UX than rendering an empty list for an edge case.
