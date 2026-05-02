<?php
$extraStylesheets = [
    '/css/planner.css?v=' . rawurlencode((string) @filemtime(__DIR__ . '/../../public/css/planner.css')),
];
require __DIR__ . '/partials/header.php';
?>

<main>
    <section class="section">
        <div class="container checkout-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2">Checkout</h1>
                    <p class="text-muted mb-0">Confirm your tickets and place your order.</p>
                </div>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <?php if (!empty($planner['is_empty'])): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <h2 class="h4 mb-3">Your planner is empty</h2>
                        <p class="text-muted mb-4">Add tickets to continue checkout.</p>
                        <a class="btn cta-btn" href="/jazz">Browse events</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($planner['has_invalid_items'])): ?>
                    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="alert">
                        <div>
                            <div class="fw-semibold">Some tickets are no longer available</div>
                            <div class="small">Remove unavailable tickets from your planner before checkout.</div>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary" href="/planner">Review planner</a>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <h2 class="h5 mb-0">Your tickets</h2>
                            </div>
                            <div class="card-body">
                                <?php
                                $checkoutItemsByEventId = [];
                                $pairedEventByEventId = [];

                                foreach ((array) ($planner['items'] ?? []) as $plannerItem) {
                                    $checkoutItemsByEventId[(int) ($plannerItem['event_id'] ?? 0)] = $plannerItem;
                                }

                                foreach ((array) ($planner['time_conflict_pairs'] ?? []) as $pair) {
                                    $leftEventId = (int) ($pair['left_event_id'] ?? 0);
                                    $rightEventId = (int) ($pair['right_event_id'] ?? 0);

                                    if ($leftEventId <= 0 || $rightEventId <= 0) {
                                        continue;
                                    }

                                    if (!isset($checkoutItemsByEventId[$leftEventId]) || !isset($checkoutItemsByEventId[$rightEventId])) {
                                        continue;
                                    }

                                    if (!isset($pairedEventByEventId[$leftEventId])) {
                                        $pairedEventByEventId[$leftEventId] = $rightEventId;
                                    }
                                    if (!isset($pairedEventByEventId[$rightEventId])) {
                                        $pairedEventByEventId[$rightEventId] = $leftEventId;
                                    }
                                }

                                $renderedCheckoutEvents = [];
                                ?>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ((array) ($planner['items'] ?? []) as $item): ?>
                                        <?php
                                        $eventId = (int) ($item['event_id'] ?? 0);
                                        if (isset($renderedCheckoutEvents[$eventId])) {
                                            continue;
                                        }

                                        $pairedEventId = (int) ($pairedEventByEventId[$eventId] ?? 0);
                                        $hasPair = $pairedEventId > 0
                                            && isset($checkoutItemsByEventId[$pairedEventId])
                                            && !isset($renderedCheckoutEvents[$pairedEventId]);
                                        ?>

                                        <?php if ($hasPair): ?>
                                            <?php $pairedItem = (array) $checkoutItemsByEventId[$pairedEventId]; ?>
                                            <li class="list-group-item px-0">
                                                <section class="checkout-conflict-paired-events" data-conflict-left-event-id="<?php echo $eventId; ?>" data-conflict-right-event-id="<?php echo $pairedEventId; ?>" role="note" aria-label="Conflicting events">
                                                    <h3 class="checkout-conflict-paired-title">Conflicting events</h3>

                                                    <div class="checkout-ticket-row">
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars((string) $item['time'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="small text-muted">x<?php echo (int) $item['quantity']; ?></div>
                                                        </div>
                                                    </div>

                                                    <div class="checkout-ticket-row">
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars((string) $pairedItem['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars((string) $pairedItem['time'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="small text-muted">x<?php echo (int) $pairedItem['quantity']; ?></div>
                                                        </div>
                                                    </div>
                                                </section>
                                            </li>
                                            <?php
                                            $renderedCheckoutEvents[$eventId] = true;
                                            $renderedCheckoutEvents[$pairedEventId] = true;
                                            ?>
                                        <?php else: ?>
                                            <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars((string) $item['time'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="small text-muted">x<?php echo (int) $item['quantity']; ?></div>
                                                </div>
                                            </li>
                                            <?php $renderedCheckoutEvents[$eventId] = true; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <span class="badge text-bg-light border"><?php echo (int) $planner['total_quantity']; ?> tickets</span>
                                    <div class="fs-5 fw-semibold">Total: &euro;<?php echo htmlspecialchars((string) $planner['total_price'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <?php if (!empty($requires_details)): ?>
                                    <h2 class="h5 mb-2">Your contact details</h2>
                                    <p class="text-muted small mb-3">We need your name, address, city, country, and phone number to process your order.</p>

                                    <form method="post" action="/checkout/details" class="row g-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold" for="checkout-first-name">First name</label>
                                            <input id="checkout-first-name" class="form-control" type="text" name="first_name" value="<?php echo htmlspecialchars((string) ($user->firstName() ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold" for="checkout-last-name">Last name</label>
                                            <input id="checkout-last-name" class="form-control" type="text" name="last_name" value="<?php echo htmlspecialchars((string) ($user->lastName() ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" for="checkout-address">Address</label>
                                            <input id="checkout-address" class="form-control" type="text" name="address" value="<?php echo htmlspecialchars((string) ($user->address() ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold" for="checkout-city">City</label>
                                            <input id="checkout-city" class="form-control" type="text" name="city" value="<?php echo htmlspecialchars((string) ($user->city() ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold" for="checkout-country">Country</label>
                                            <input id="checkout-country" class="form-control" type="text" name="country" value="<?php echo htmlspecialchars((string) ($user->country() ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" for="checkout-phone">Phone number</label>
                                            <input id="checkout-phone" class="form-control" type="text" name="phone_number" value="<?php echo htmlspecialchars((string) ($user->phoneNumber() ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12 d-grid">
                                            <button type="submit" class="btn cta-btn">Save details</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <h2 class="h5 mb-2">Review order</h2>
                                    <p class="text-muted small mb-3">Enter mock payment details or leave them empty, then place your order.</p>

                                    <form method="post" action="/checkout/confirm" class="row g-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                                        <div class="col-12">
                                            <label class="form-label fw-semibold" for="payment-card-name">Name on card</label>
                                            <input id="payment-card-name" class="form-control" type="text" name="card_name" autocomplete="cc-name">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" for="payment-card-number">Card number</label>
                                            <input id="payment-card-number" class="form-control" type="text" name="card_number" inputmode="numeric" autocomplete="cc-number">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="payment-card-expiry">Expiry</label>
                                            <input id="payment-card-expiry" class="form-control" type="text" name="card_expiry" placeholder="MM/YY" autocomplete="cc-exp">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="payment-card-cvc">CVC</label>
                                            <input id="payment-card-cvc" class="form-control" type="text" name="card_cvc" inputmode="numeric" autocomplete="cc-csc">
                                        </div>

                                        <div class="col-12 d-flex flex-wrap gap-2">
                                            <a href="/planner" class="btn btn-outline-secondary">Back to Planner</a>
                                            <button type="submit" class="btn cta-btn">Place order</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
