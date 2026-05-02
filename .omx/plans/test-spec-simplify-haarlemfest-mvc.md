# Test Spec: Simplify HaarlemFest MVC Checkout

## Static Verification
- Run PHP syntax checks over `app/src` and `app/public`.
- Run deletion-manifest grep over `app/src` and `app/public`; expect zero hits.
- Run `composer dump-autoload`.

## Automated Tests
- Run `vendor/bin/phpunit` in `app/`.
- Reconcile tests that assert deleted hold/handoff/idempotency invariants.

## Behavioral Checks
- Checkout with valid planner items returns success and clears planner.
- Empty planner returns an error array.
- Missing user details return an error array.
- Repository creates one invoice and one ticket row per purchased ticket.
- Event stock is decremented only inside the checkout transaction.

## Manual Scenario When Runtime DB Is Available
- Log in as a complete user.
- Add two tickets for one stocked event.
- POST `/checkout/confirm`.
- Confirm redirect to `/orders`, one new invoice, two new tickets, stock decremented by two, and orders page renders the purchase.
