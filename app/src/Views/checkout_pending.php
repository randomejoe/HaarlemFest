<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$attemptStatus = (string) ($attempt['status'] ?? '');
$statusBadgeClass = 'text-bg-secondary';
if ($attemptStatus === 'handoff_created') {
    $statusBadgeClass = 'text-bg-warning';
} elseif ($attemptStatus === 'paid') {
    $statusBadgeClass = 'text-bg-success';
} elseif ($attemptStatus === 'handoff_failed') {
    $statusBadgeClass = 'text-bg-danger';
} elseif ($attemptStatus === 'expired') {
    $statusBadgeClass = 'text-bg-dark';
}
?>

<main>
    <section class="section">
        <div class="container checkout-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2">Payment Pending</h1>
                    <p class="text-muted mb-0">Your checkout was handed off.</p>
                </div>
                <span class="badge <?php echo htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?> px-3 py-2 text-uppercase">
                    <?php echo htmlspecialchars($attemptStatus, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h2 class="h5 mb-0">Checkout Status</h2>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-4 small">
                                <li class="d-flex justify-content-between py-1"><span class="text-muted">Attempt</span><span class="fw-semibold">#<?php echo (int) ($attempt['checkout_attempt_id'] ?? 0); ?></span></li>
                                <li class="d-flex justify-content-between py-1"><span class="text-muted">Provider</span><span><?php echo htmlspecialchars((string) ($attempt['payment_provider'] ?? 'stub_provider'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                                <li class="d-flex justify-content-between py-1"><span class="text-muted">Reference</span><span><?php echo htmlspecialchars((string) ($attempt['payment_reference'] ?? 'n/a'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                                <li class="d-flex justify-content-between py-1"><span class="text-muted">Hold expires</span><span><?php echo htmlspecialchars((string) ($attempt['hold_expires_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></li>
                                <li class="d-flex justify-content-between py-1 border-top mt-2 pt-2"><span class="text-muted">Total</span><span class="fw-semibold fs-6">&euro;<?php echo htmlspecialchars((string) number_format((float) ($attempt['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></span></li>
                            </ul>

                            <?php if ($attemptStatus === 'handoff_created'): ?>
                                <form method="post" action="/checkout/pending/<?php echo (int) ($attempt['checkout_attempt_id'] ?? 0); ?>/confirm" class="d-grid">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn cta-btn">Temporary Confirm Payment</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">Held Events</h2>
                            <span class="badge text-bg-light border"><?php echo count($items); ?> lines</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($items)): ?>
                                <p class="text-muted mb-0">No held items found for this attempt.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($items as $item): ?>
                                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($item['name'] ?? 'Event unavailable'), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars((string) (($item['start_time'] ?? '') . ' - ' . ($item['end_time'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted">x<?php echo (int) ($item['quantity'] ?? 0); ?></div>
                                                <div class="fw-semibold">&euro;<?php echo htmlspecialchars((string) number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="/checkout" class="btn btn-outline-secondary">Back to Checkout</a>
                                <a href="/planner" class="btn cta-btn">View Planner</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
