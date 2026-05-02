# PRD: Simplify HaarlemFest MVC Checkout

## Goal
Replace the generated hold/payment-handoff checkout architecture with a course-appropriate MVC flow that creates invoices and tickets directly from planner contents.

## Scope
- Delete payment handoff, stock reservation holds, checkout attempts, idempotency, pending checkout views/routes, and unused support classes listed in the plan.
- Keep invoice/ticket PDF and delivery classes because the assignment still needs them.
- Preserve CSRF validation and view token injection.
- Preserve existing browse/planner/checkout/orders user flow.

## Acceptance Criteria
- Checkout controller exposes `show`, `saveDetails`, and `confirm`.
- Checkout service confirms checkout through a PDO transaction.
- Checkout repository exposes only `findEventForUpdate`, `decrementStock`, `createInvoice`, and `createTicket`.
- `findEventForUpdate` contains `FOR UPDATE`.
- Pending checkout routes and view are removed.
- Deletion-manifest grep has zero hits in `app/src` and `app/public`.
- `composer dump-autoload`, PHP syntax checks, and PHPUnit pass.

## Out Of Scope
- Dropping `checkout_attempts` or `ticket_holds` tables.
- New dependencies or new architectural layers.
- Renaming invoice persistence vocabulary.
