# Test Spec: Targeted Review Fixes - Auth, Planner, Checkout

## Static Verification
- Run `php -l` on each modified PHP file.
- Grep for removed constructs:
  - `DeliveryResult`
  - `deliverPurchaseEmails`
  - `match (` in `AccountController`
  - `array_map('trim', $_POST)`
  - `normalizeViewData`
  - leading tabs in `PlannerController`

## Unit / Regression Verification
- Run targeted PHPUnit tests for checkout/planner/auth where available.
- Update checkout service test expectations to the new ticket delivery interface.

## Behavioral Smoke
- Confirm checkout success path still returns `['success' => true, 'order_id' => ...]` when email delivery is attempted.
- Confirm the delivery layer catches mail/PDF exceptions internally so checkout success is not blocked.
- Confirm planner add/update operations keep the uniform array item shape with and without family ticket flag.
