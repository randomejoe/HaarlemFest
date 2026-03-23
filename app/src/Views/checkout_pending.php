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
$attemptStatusLabel = match ($attemptStatus) {
    'handoff_created' => 'Payment pending',
    'paid' => 'Payment confirmed',
    'handoff_failed' => 'Payment failed',
    'expired' => 'Session expired',
    default => 'Payment status',
};
$pageSubtitle = match ($attemptStatus) {
    'handoff_created' => 'Your payment is being processed.',
    'paid' => 'Payment confirmed. Your tickets are being sent.',
    'handoff_failed' => 'We could not complete your payment.',
    'expired' => 'Your payment session expired. Please start checkout again.',
    default => 'Your payment status.',
};

$ticketCount = array_sum(array_map(
    static fn($i): int => (int) ($i['quantity'] ?? 0),
    (array) ($items ?? [])
));
?>

<main>
    <section class="section">
        <div class="container checkout-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2"><?php echo htmlspecialchars($attemptStatusLabel, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <span class="badge <?php echo htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?> px-3 py-2">
                    <?php echo htmlspecialchars($attemptStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h2 class="h5 mb-0">Order summary</h2>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-4 small">
                                <li class="d-flex justify-content-between py-1">
                                    <span class="text-muted">Reserved until</span>
                                    <span><?php echo htmlspecialchars((string) ($attempt['hold_expires_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-1 border-top mt-2 pt-2"><span class="text-muted">Total</span><span class="fw-semibold fs-6">&euro;<?php echo htmlspecialchars((string) number_format((float) ($attempt['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></span></li>
                            </ul>

                            <?php if ($attemptStatus === 'handoff_created'): ?>
                                <form method="post" action="/checkout/pending/<?php echo (int) ($attempt['checkout_attempt_id'] ?? 0); ?>/confirm" class="d-grid">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" for="card-number">Card Number</label>
                                            <input
                                                id="card-number"
                                                class="form-control"
                                                type="text"
                                                name="card_number"
                                                inputmode="numeric"
                                                autocomplete="cc-number"
                                            >
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="exp-month">Exp Month</label>
                                            <input
                                                id="exp-month"
                                                class="form-control"
                                                type="text"
                                                name="exp_month"
                                                inputmode="numeric"
                                                autocomplete="cc-exp-month"
                                            >
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="exp-year">Exp Year</label>
                                            <input
                                                id="exp-year"
                                                class="form-control"
                                                type="text"
                                                name="exp_year"
                                                inputmode="numeric"
                                                autocomplete="cc-exp-year"
                                            >
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold" for="cvc">CVC</label>
                                            <input
                                                id="cvc"
                                                class="form-control"
                                                type="text"
                                                name="cvc"
                                                inputmode="numeric"
                                                autocomplete="cc-csc"
                                            >
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold" for="cardholder-name">Cardholder Name</label>
                                            <input
                                                id="cardholder-name"
                                                class="form-control"
                                                type="text"
                                                name="cardholder_name"
                                                autocomplete="cc-name"
                                            >
                                        </div>
                                    </div>

                                    <button type="submit" class="btn cta-btn mt-3">Pay now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">Tickets in your order</h2>
                            <span class="badge text-bg-light border"><?php echo (int) $ticketCount; ?> tickets</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($items)): ?>
                                <p class="text-muted mb-0">No tickets found for this checkout.</p>
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
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Removed Back to checkout and Edit tickets buttons as requested -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
