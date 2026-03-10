<?php require __DIR__ . '/partials/header.php'; ?>

<main class="jazz-page">
    <section class="jazz-hero">
        <div class="container jazz-hero-container">
            <a class="jazz-back-link" href="/#events">Back to Jazz Event</a>
            <h1>Festival Program by Day</h1>
            <p>Browse all events per day. Add individual performances or reservations to your personal program and check out when ready.</p>
        </div>
    </section>

    <section class="jazz-tabs-wrap">
        <div class="container">
            <div class="jazz-day-tabs" role="tablist" aria-label="Festival days">
                <?php foreach ($days as $day): ?>
                    <a
                        class="jazz-day-tab<?php echo $selected_day === $day['key'] ? ' active' : ''; ?>"
                        href="/jazz?day=<?php echo urlencode($day['key']); ?>"
                        role="tab"
                        aria-selected="<?php echo $selected_day === $day['key'] ? 'true' : 'false'; ?>"
                    >
                        <span class="jazz-day-tab-day"><?php echo htmlspecialchars($day['label_day'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="jazz-day-tab-date"><?php echo htmlspecialchars($day['label_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="jazz-program">
        <div class="container">
            <?php if (!empty($planner_flash['message'])): ?>
                <p class="account-message <?php echo htmlspecialchars((string) ($planner_flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $planner_flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <?php if (empty($events)): ?>
                <div class="jazz-empty-state">
                    No Jazz events available for this day.
                </div>
            <?php else: ?>
                <div class="row g-4 jazz-program-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="col-12 col-xl-6">
                            <article class="jazz-event-card h-100">
                                <h3><?php echo htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <ul class="jazz-event-meta">
                                    <li><?php echo htmlspecialchars($event['time'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><?php echo htmlspecialchars($event['venue'], ENT_QUOTES, 'UTF-8'); ?></li>
                                </ul>
                                <p class="jazz-event-description"><?php echo htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="jazz-event-summary<?php echo empty($event['availability_label']) ? ' no-availability' : ''; ?>">
                                    <?php if (!empty($event['availability_label'])): ?>
                                        <p class="jazz-availability mb-0"><?php echo htmlspecialchars((string) $event['availability_label'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                    <div class="jazz-price-wrap<?php echo empty($event['status']) ? ' price-only' : ''; ?>">
                                        <?php if (!empty($event['status'])): ?>
                                            <span class="jazz-status <?php echo htmlspecialchars((string) $event['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars((string) $event['status'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="jazz-price<?php echo !empty($event['is_free']) ? ' is-free' : ''; ?>">
                                            <?php echo !empty($event['is_free']) ? 'FREE' : '&euro;' . htmlspecialchars($event['price'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if (!empty($event['can_add_to_planner'])): ?>
                                    <div class="jazz-card-actions">
                                        <form method="post" action="/planner/items" class="jazz-add-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="event_id" value="<?php echo (int) $event['event_id']; ?>">
                                            <input type="hidden" name="return_to" value="/jazz?day=<?php echo urlencode((string) $selected_day); ?>">

                                            <div class="jazz-qty" aria-label="Quantity selector">
                                                <label for="qty-<?php echo (int) $event['event_id']; ?>" class="visually-hidden">Quantity</label>
                                                <input
                                                    id="qty-<?php echo (int) $event['event_id']; ?>"
                                                    class="jazz-qty-input"
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    name="quantity"
                                                    value="1"
                                                    required
                                                >
                                            </div>

                                            <button type="submit" class="jazz-add-btn" <?php echo !empty($planner_locked) ? 'disabled' : ''; ?>>
                                                Add to Planner
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="jazz-planner-bar">
        <div class="container">
            <div class="jazz-planner-inner">
                <div class="jazz-planner-copy">
                    <h2>Your Personal Planner</h2>
                    <p><?php echo (int) $planner_count; ?> items &bull; Total: &euro;<?php echo htmlspecialchars((string) $planner_total, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (!empty($planner_locked)): ?>
                        <p class="jazz-lock-note">Planner is locked while payment is pending.</p>
                    <?php endif; ?>
                </div>
                <div class="jazz-planner-actions">
                    <a href="/planner" class="jazz-outline-btn">View Planner</a>
                    <a href="/checkout" class="jazz-primary-btn">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
