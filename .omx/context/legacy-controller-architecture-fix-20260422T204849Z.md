# Legacy Controller Architecture Fix Context Snapshot

Task statement: Use Ralph and Team mode to implement `.omc/plans/legacy-controller-architecture-fix-plan.md`.

Desired outcome: The legacy Auth, Account, and Orders controllers no longer depend directly on repositories; touched services and repositories expose/use interfaces according to the rulebook; PageRepository no longer owns content-item transaction/sanitation/authz flow; verification evidence is collected before completion.

Known facts/evidence:
- Plan file exists at `.omc/plans/legacy-controller-architecture-fix-plan.md`.
- Rulebook exists at `.claude/CLAUDE.md`.
- Current repo already has many modified/untracked files from an in-flight architecture refactor; do not revert unrelated changes.
- `tmux -V` reports tmux 3.6a and `omx --version` reports oh-my-codex v0.11.12.
- This desktop thread is not inside a tmux session (`$TMUX` empty), so use OMX programmatic team runner rather than interactive split-pane CLI.
- Current targeted controllers still import repositories directly.
- Existing interface directories are `app/src/Repositories/Interfaces` and `app/src/Services/Interfaces`.

Constraints:
- Follow `.claude/CLAUDE.md`: Controller -> Service -> Repository -> Database, controllers must not use repositories, every touched service/repository needs an interface.
- No new dependencies.
- Keep diffs small and preserve behavior except the documented OrdersController stale-session delta.
- Do not modify views or schema for this plan.
- Respect existing dirty worktree; do not revert user/team changes.

Unknowns/open questions:
- Full PHP test suite status before this specific work is unknown.
- Runtime smoke tests may be limited by local server/database availability.
- Existing uncommitted architecture work may already partially overlap with planned changes.

Likely codebase touchpoints:
- `app/src/Controllers/AuthController.php`
- `app/src/Controllers/AccountController.php`
- `app/src/Controllers/OrdersController.php`
- `app/src/Repositories/Interfaces/IUserRepository.php`
- `app/src/Repositories/Interfaces/IOrderRepository.php`
- `app/src/Repositories/UserRepository.php`
- `app/src/Repositories/OrderRepository.php`
- `app/src/Repositories/PageRepository.php`
- `app/src/Models/User.php`
- `app/src/Services/Interfaces/IAuthService.php`
- `app/src/Services/Interfaces/IAccountService.php`
- `app/src/Services/Interfaces/IOrderService.php`
- `app/src/Services/AuthService.php`
- `app/src/Services/AccountService.php`
- `app/src/Services/OrderService.php`
- `app/src/Services/ContentService.php`
- `app/public/index.php`
