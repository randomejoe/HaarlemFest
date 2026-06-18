<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section">
        <div class="container orders-wrap">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="mb-2">My Orders</h1>
                    <p class="text-muted mb-0">View your purchased tickets here.</p>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <h2 class="h4 mb-3">No orders yet</h2>
                        <p class="text-muted mb-4">Your paid orders will appear here once checkout is completed.</p>
                        <a class="btn cta-btn" href="/jazz">Explore events</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-grid gap-3">
                    <?php foreach ($orders as $order): ?>
                        <article class="card shadow-sm border-0">
                            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <h2 class="h5 mb-1">Order #<?php echo (int) ($order['invoice_id'] ?? 0); ?></h2>
                                    <p class="small text-muted mb-0">Placed: <?php echo htmlspecialchars((string) ($order['created_at_formatted'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge text-bg-light border px-3 py-2"><?php echo (int) ($order['ticket_count'] ?? 0); ?> tickets</span>
                                    <span class="badge text-bg-success px-3 py-2">&euro;<?php echo htmlspecialchars((string) ($order['total_price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <?php if (empty($order['items'])): ?>
                                    <div class="p-3 text-muted">No ticket items were found for this order.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Schedule</th>
                                                    <th>Qty</th>
                                                    <th>Line Total</th>
                                                    <th>Ticket Numbers</th>
                                                    <th>Verification Codes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order['items'] as $item):?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars((string) ($item['event_name'] ?? 'Event'), ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars((string) ($item['venue'] ?? 'Venue to be announced'), ENT_QUOTES, 'UTF-8'); ?></div>
                                                        </td>
                                                        <td class="small"><?php echo htmlspecialchars((string) ($item['schedule'] ?? 'Schedule unavailable'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><span class="badge text-bg-light border"><?php echo (int) ($item['quantity'] ?? 0); ?></span></td>
                                                        <td class="fw-semibold">&euro;<?php echo htmlspecialchars((string) ($item['line_total'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ((array) ($item['ticket_numbers'] ?? []) as $ticketNumber): ?>
                                                                    <span class="badge text-bg-light border"><?php echo htmlspecialchars((string) $ticketNumber, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ((array) ($item['verification_codes'] ?? []) as $code): ?>
                                                                    <span class="badge rounded-pill" style="background:#fff2f5;color:#8f1231;border:1px solid #f3bcc8;">
                                                                        <?php echo htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
