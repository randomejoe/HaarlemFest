# PRD: Targeted Review Fixes - Auth, Planner, Checkout

## Goal
Apply the approved review fixes exactly as specified without widening scope beyond Auth, Planner, and Checkout flows.

## Scope
- Auth: replace `match` in `AccountController`.
- Checkout: simplify ticket delivery, remove `DeliveryResult`, harden POST details handling.
- Planner: unify item shape, use strict comparison, remove controller dead code, normalize indentation.

## Non-Goals
- No CMS/Page changes.
- No repository architecture changes.
- No new dependencies.
- No redesign of checkout, payment, planner, or auth flows.

## Acceptance Criteria
- All specified grep checks pass.
- PHP lint passes for every modified PHP file.
- Checkout service calls `sendOrderConfirmation()` exactly once and does not reference `DeliveryResult`.
- Planner service has no mixed item-shape branches and stores item arrays uniformly.
- Planner controller has no leading tabs or `normalizeViewData()`.
- Existing service interfaces remain in use by controllers/services.
