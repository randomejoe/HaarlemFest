# Planner Checkout Rebuild Context

Task statement:
Implement `.omc/plans/planner-checkout-rebuild-plan.md` strictly following `.claude/CLAUDE.md`.

Desired outcome:
Rebuild planner and checkout backend with Controller -> Service -> Repository -> Database flow, interfaces for services/repositories, no `Container.php`, manual wiring in `app/public/index.php`, tests, and rulebook compliance checks.

Known facts/evidence:
- PHP app rooted at `/Users/joedavtian/HaarlemFest/app`.
- Composer project uses FastRoute and PHPUnit; test command is `composer test` from `app/`.
- Current entrypoint uses `App\Container`; plan requires deleting it and wiring direct instances in `app/public/index.php`.
- Current modified/untracked files before work: `.gitignore` modified, `.omc/` and `.omx/` untracked.
- This Codex thread is not inside tmux, so team execution should use OMX MCP lifecycle tools rather than interactive `omx team` pane splitting.

Constraints:
- Follow `.claude/CLAUDE.md`: controllers receive requests only, services own business logic, repositories own DB operations, views are display only, dependencies flow downward.
- No new dependencies.
- Do not change views, auth/CMS/order-history services/controllers except wiring required by deleting `Container.php`.
- Only schema delta is idempotency unique-index migration.
- Keep diffs focused and preserve existing CMS `EventRepository` method signatures.
- Do not revert user changes.

Unknowns/open questions:
- Current DB engine/migration naming conventions may differ from plan's suggested `.sql`; inspect existing Phinx migrations before choosing final migration shape.
- Existing test DB capabilities need verification before adding integration coverage.
- Existing `UserRepository` call surface must be checked before creating `IUserRepository`.

Likely codebase touchpoints:
- `app/src/Controllers/PlannerController.php`
- `app/src/Controllers/CheckoutController.php`
- `app/src/Services/{PlannerService,CheckoutService,SessionManager,CheckoutHoldManager,StockReservationService,PaymentHandoffService,TicketDeliveryService}.php`
- `app/src/Repositories/{CheckoutRepository,TicketHoldRepository,EventRepository,UserRepository}.php`
- `app/src/Models/*`
- `app/src/ViewModels/*`
- `app/public/index.php`
- `app/db/migrations/*`
- `app/tests/*`
- `scripts/verify_rulebook_compliance.sh`
