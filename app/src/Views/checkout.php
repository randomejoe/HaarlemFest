<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section">
        <div class="container checkout-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2">Checkout</h1>
                    <p class="text-muted mb-0">Confirm your planner and continue to payment handoff.</p>
                </div>
                <a href="/planner" class="btn btn-outline-secondary">Back to Planner</a>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <?php if (!empty($planner['is_empty'])): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <h2 class="h4 mb-3">Your planner is empty</h2>
                        <p class="text-muted mb-4">Add events first to continue checkout.</p>
                        <a class="btn cta-btn" href="/jazz">Browse events</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($planner['has_invalid_items'])): ?>
                    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="alert">
                        <div>
                            <div class="fw-semibold">Unavailable items found</div>
                            <div class="small">Remove unavailable events from your planner before checkout.</div>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary" href="/planner">Review planner</a>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <h2 class="h5 mb-0">Selected Events</h2>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($planner['items'] as $item): ?>
                                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars((string) $item['time'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted">x<?php echo (int) $item['quantity']; ?></div>
                                                <div class="fw-semibold">&euro;<?php echo htmlspecialchars((string) $item['line_total'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <?php if (!empty($planner['time_conflicts'])): ?>
                                    <div class="alert alert-warning mb-3" role="alert">
                                        <h3 class="h6 mb-2">Time overlap warning</h3>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($planner['time_conflicts'] as $conflict): ?>
                                                <li><?php echo htmlspecialchars((string) $conflict, ENT_QUOTES, 'UTF-8'); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <span class="badge text-bg-light border"><?php echo (int) $planner['total_quantity']; ?> items</span>
                                    <div class="fs-5 fw-semibold">Total: &euro;<?php echo htmlspecialchars((string) $planner['total_price'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <?php if (!empty($requires_details)): ?>
                                    <h2 class="h5 mb-2">Complete Required Details</h2>
                                    <p class="text-muted small mb-3">We need your name, address, city, country, and phone number before checkout.</p>

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
                                            <button type="submit" class="btn cta-btn">Save Details</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <h2 class="h5 mb-2">Confirm and Continue</h2>
                                    <p class="text-muted small mb-3">Tickets are held for 10 minutes while creating payment handoff.</p>

                                    <form method="post" action="/checkout/confirm" class="d-grid gap-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="idempotency_key" value="<?php echo htmlspecialchars((string) $idempotency_key, ENT_QUOTES, 'UTF-8'); ?>">

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="simulate-handoff-failure" name="simulate_handoff_failure" value="1">
                                            <label class="form-check-label small" for="simulate-handoff-failure">Simulate payment handoff failure (testing only)</label>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="/planner" class="btn btn-outline-secondary">Back to Planner</a>
                                            <button type="submit" class="btn cta-btn">Confirm and Start Payment</button>
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
