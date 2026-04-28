#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

fail() {
  echo "FAIL: $1" >&2
  exit 1
}

assert_absent() {
  local label="$1"
  local pattern="$2"
  local file="$3"

  if grep -nF "$pattern" "$ROOT_DIR/$file" >/dev/null; then
    echo "FAIL: $label" >&2
    grep -nF "$pattern" "$ROOT_DIR/$file" >&2 || true
    exit 1
  fi
}

assert_present() {
  local label="$1"
  local pattern="$2"
  local file="$3"

  if ! grep -nF "$pattern" "$ROOT_DIR/$file" >/dev/null; then
    fail "$label"
  fi
}

assert_absent "Planner controller must not import repositories" "App\Repositories" "app/src/Controllers/PlannerController.php"
assert_absent "Checkout controller must not import repositories" "App\Repositories" "app/src/Controllers/CheckoutController.php"
assert_absent "Checkout controller must not mention concrete planner service" "use App\Services\PlannerService" "app/src/Controllers/CheckoutController.php"
assert_absent "Checkout controller must not mention concrete checkout service" "use App\Services\CheckoutService" "app/src/Controllers/CheckoutController.php"
assert_absent "Checkout controller must not mention user repository" "UserRepository" "app/src/Controllers/CheckoutController.php"
assert_absent "Checkout controller must not mention checkout repository" "CheckoutRepository" "app/src/Controllers/CheckoutController.php"
assert_present "Planner controller must depend on IPlannerService" "IPlannerService" "app/src/Controllers/PlannerController.php"
assert_present "Checkout controller must depend on ICheckoutService" "ICheckoutService" "app/src/Controllers/CheckoutController.php"
assert_present "Checkout controller must depend on AuthService" "AuthService" "app/src/Controllers/CheckoutController.php"

echo "OK: rulebook compliance checks passed"
