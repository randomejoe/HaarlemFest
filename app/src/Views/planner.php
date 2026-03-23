<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section">
        <div class="container planner-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2">Your Planner</h1>
                    <p class="text-muted mb-0">Review, adjust, and checkout.</p>
                </div>
                <?php if (!empty($planner['is_locked'])): ?>
                    <span class="badge rounded-pill text-bg-warning">Payment in progress</span>
                <?php endif; ?>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <?php if (!empty($planner['time_conflicts'])): ?>
                <div class="alert alert-warning mb-4" role="alert">
                    <h2 class="h6 mb-2">Schedule conflicts</h2>
                    <ul class="mb-0 ps-3">
                        <?php
                        $conflicts = (array) $planner['time_conflicts'];
                        $max = 3;
                        $shown = 0;
                        foreach ($conflicts as $conflict):
                            if ($shown >= $max) {
                                break;
                            }
                            $shown++;
                        ?>
                            <li><?php echo htmlspecialchars((string) $conflict, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                        <?php if (count($conflicts) > $max): ?>
                            <li class="fst-italic"><?php echo htmlspecialchars('And more...', ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($planner['is_empty'])): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <h2 class="h4 mb-3">Your planner is empty</h2>
                        <p class="text-muted mb-4">Add tickets to get started.</p>
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
                                        <th>When</th>
                                        <th>Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planner['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if (empty($item['is_valid'])): ?>
                                                    <div class="small text-danger mt-1">Not available</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <?php if (!empty($item['is_valid'])): ?>
                                                    <?php echo htmlspecialchars((string) $item['time'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars('Unavailable', ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['is_valid']) && empty($planner['is_locked'])): ?>
                                                    <div class="d-flex flex-column align-items-start gap-2">
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

                                                        <form method="post" action="/planner/items/<?php echo (int) $item['event_id']; ?>/remove" class="m-0">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge text-bg-light border">x<?php echo (int) $item['quantity']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-semibold text-end">
                                                <?php if (!empty($item['is_valid'])): ?>
                                                    &euro;<?php echo htmlspecialchars((string) $item['line_total'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php else: ?>
                                                    &mdash;
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
                            <span class="badge text-bg-light border px-3 py-2"><?php echo (int) $planner['total_quantity']; ?> tickets</span>
                            <span class="fw-semibold">Total: &euro;<?php echo htmlspecialchars((string) $planner['total_price'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($planner['is_locked'])): ?>
                                <a href="/checkout/pending/<?php echo (int) $planner['locked_checkout_attempt_id']; ?>" class="btn cta-btn">View payment status</a>
                            <?php else: ?>
                                <form method="post" action="/planner/clear" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">Clear tickets</button>
                                </form>
                                <a href="/checkout" class="btn cta-btn">Proceed to checkout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
