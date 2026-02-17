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
                                <div class="jazz-event-summary">
                                    <p class="mb-0"><?php echo (int) $event['seat_count']; ?> seats available</p>
                                    <div class="jazz-price-wrap">
                                        <span class="jazz-status <?php echo htmlspecialchars($event['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($event['status'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <span class="jazz-price">&euro;<?php echo htmlspecialchars($event['price'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <div class="jazz-card-actions">
                                    <div class="jazz-qty" aria-label="Quantity selector">
                                        <button type="button" aria-label="Decrease quantity" disabled>-</button>
                                        <span>1</span>
                                        <button type="button" aria-label="Increase quantity">+</button>
                                    </div>
                                    <button type="button" class="jazz-add-btn">Add to Program</button>
                                </div>
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
                </div>
                <div class="jazz-planner-actions">
                    <button type="button" class="jazz-outline-btn">View Program</button>
                    <button type="button" class="jazz-primary-btn">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
