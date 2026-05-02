# Open Questions Log

Tracking open questions across planner plans. Append-only.

---

## planner-checkout-rebuild-plan — 2026-04-21

- [ ] **UNIQUE index veto fallback** — If operators veto `ADD UNIQUE INDEX uq_checkout_attempts_idempotency_key` on `checkout_attempts`, is the `SELECT ... FOR UPDATE` on `users.user_id` fallback acceptable, or should we escalate to a different mechanism (Redis SETNX / advisory lock)? — Matters because concurrent-tab race (pre-mortem scenario d) is otherwise probabilistic.
- [ ] **Email delivery atomicity** — Is it acceptable that `PaymentConfirmationResult.status='paid'` can be returned with `emailWarning != null` (i.e., payment succeeds but email fails)? Plan assumes yes, surfaces warning via flash, and follow-up is a resend endpoint. Confirm. — Matters because alternative is fail the whole confirmation, which creates worse UX for a network blip.
- [ ] **Sweeper cadence** — Keep the existing 60-second session-cooldown-backed sweeper, or commit to a cron-driven sweeper in this PR? Plan keeps the session-cooldown sweeper (idle users never fire it) and logs the cron follow-up out of scope. — Matters because an idle DB can accumulate `initiated` attempts whose holds have expired until the next browser hit.
- [ ] **PaymentGatewayStubService reconciliation follow-up** — Pre-mortem scenario (b) (handoff success + TX2 commit failure) is currently only recoverable via hold expiry. For the production gateway (non-stub), do we commit to an orphan-handoff reconciliation job in a follow-up PR, or accept the 10-minute eventual consistency? — Matters because real gateways charge before our TX2 runs.

---

## legacy-controller-architecture-fix-plan — 2026-04-22

- [x] **PageRepository::updateContentItem caller path** — RESOLVED (Iteration 2). Grep confirmed the actual callers are `ContentService::update()` (line 98) and `ContentService::updateWithImage()` (line 70). `PageService` does NOT call `updateContentItem`. Iteration 1's routing through `PageService::updateContentItem` was a misdiagnosis; Iteration 2 routes the transaction/sanitation/authz fix through `ContentService::persistSanitized` instead.
- [x] **View references to `UserRepository::*_MAX_LENGTH`** — RESOLVED (Iteration 2). `grep -rn "MAX_LENGTH" app/src/Views/` returns 0 matches. Backward-compat aliases on `UserRepository` are NOT needed; the eight constants are removed outright and live only on `App\Models\User`.
- [ ] **OrdersController defensive `findById` check** — Plan removes the stale-session `findById` check in favor of rendering an empty orders list. If the team prefers the current defensive logout, introduce `IAuthService::refreshUser(int $id): ?User` and preserve the existing behavior. — Matters because the current behavior destroys the session on a GET when the user row is missing; the proposed behavior renders an empty page.
- [ ] **PasswordResetService scope** — Plan leaves `PasswordResetService` with a concrete `UserRepository` dependency (not a Rule 9 violation — it's a service-to-repo, not controller-to-repo). Should a follow-up migrate it to `IUserRepository` for consistency, or is the scope boundary acceptable? — Matters for long-term consistency but not for rule compliance.
- [ ] **AuthService factory atomicity (R7 follow-up)** — Step 2 (AuthService constructor change) and Step 7 (index.php factory update) must land in a single commit to avoid breaking `PasswordResetService` resolution. Verified in Step 8 smoke test #13 (`POST /password/forgot`). If a future change splits these across commits, the chain breaks. — Matters because `PasswordResetService` resolves `AuthService` at runtime via the DI closure.
