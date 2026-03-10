<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section">
        <div class="container planner-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2">Your Personal Planner</h1>
                    <p class="text-muted mb-0">Review, adjust and checkout.</p>
                </div>
                <?php if (!empty($planner['is_locked'])): ?>
                    <span class="badge rounded-pill text-bg-warning">Payment in process</span>
                <?php endif; ?>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <?php if (!empty($planner['time_conflicts'])): ?>
                <div class="alert alert-warning mb-4" role="alert">
                    <h2 class="h6 mb-2">Time overlap warning</h2>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($planner['time_conflicts'] as $conflict): ?>
                            <li><?php echo htmlspecialchars((string) $conflict, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($planner['is_empty'])): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <h2 class="h4 mb-3">Your planner is empty</h2>
                        <p class="text-muted mb-4">Add events first to start checkout.</p>
                        <a class="btn cta-btn" href="/jazz">Browse events</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Event</th>
                                        <th>Schedule</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Line Total</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planner['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars((string) $item['venue'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if (empty($item['is_valid'])): ?>
                                                    <div class="small text-danger mt-1"><?php echo htmlspecialchars((string) $item['invalid_reason'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small"><?php echo htmlspecialchars((string) $item['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>&euro;<?php echo htmlspecialchars((string) $item['unit_price'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if (!empty($item['is_valid']) && empty($planner['is_locked'])): ?>
                                                    <form method="post" action="/planner/items/<?php echo (int) $item['event_id']; ?>/quantity" class="d-flex align-items-center gap-2">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            name="quantity"
                                                            value="<?php echo (int) $item['quantity']; ?>"
                                                            class="form-control form-control-sm"
                                                            style="max-width: 90px;"
                                                            required
                                                        >
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge text-bg-light border"><?php echo (int) $item['quantity']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-semibold">&euro;<?php echo htmlspecialchars((string) $item['line_total'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-end">
                                                <?php if (empty($planner['is_locked'])): ?>
                                                    <form method="post" action="/planner/items/<?php echo (int) $item['event_id']; ?>/remove" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge text-bg-light border">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge text-bg-light border px-3 py-2"><?php echo (int) $planner['total_quantity']; ?> items</span>
                            <span class="fw-semibold">Total: &euro;<?php echo htmlspecialchars((string) $planner['total_price'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($planner['is_locked'])): ?>
                                <a href="/checkout/pending/<?php echo (int) $planner['locked_checkout_attempt_id']; ?>" class="btn cta-btn">View Pending Payment</a>
                            <?php else: ?>
                                <form method="post" action="/planner/clear" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">Clear Planner</button>
                                </form>
                                <a href="/checkout" class="btn cta-btn">Proceed to Checkout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
