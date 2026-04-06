<?php
$extraStylesheets = [
    '/css/planner.css?v=' . rawurlencode((string) @filemtime(__DIR__ . '/../../public/css/planner.css')),
];
require __DIR__ . '/partials/header.php';
echo '<pre>';
print_r($planner);
echo '</pre>';
?>

<main>
    <section class="section">
        <div class="container planner-wrap planner-page">
            <div class="planner-hero mb-4">
                <div class="planner-hero-copy">
                    <h1 class="planner-title">Your Planner</h1>
                    <p class="planner-intro">Review your tickets, resolve conflicts, and finish checkout.</p>
                </div>

                <div class="planner-hero-stats" aria-label="Planner summary">
                    <div class="planner-stat">
                        <span class="planner-stat-label">Tickets</span>
                        <strong data-planner-count><?php echo (int) $planner['total_quantity']; ?></strong>
                    </div>
                    <div class="planner-stat">
                        <span class="planner-stat-label">Total</span>
                        <strong>&euro;<span data-planner-total><?php echo htmlspecialchars((string) $planner['total_price'], ENT_QUOTES, 'UTF-8'); ?></span></strong>
                    </div>
                </div>
            </div>

            <?php echo \App\View::render('components/flash_alert', ['flash' => $flash]); ?>

            <?php if (!empty($planner['is_empty'])): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <h2 class="h4 mb-3">Your planner is empty</h2>
                        <p class="text-muted mb-4">Add tickets to get started.</p>
                        <a class="btn cta-btn" href="/jazz">Browse events</a>
                    </div>
                </div>
            <?php else: ?>
                <?php
                $groupedPlannerItems = [];
                $plannerItemsByEventId = [];
                $eventGroupByEventId = [];
                $pairedEventByEventId = [];

                foreach ((array) ($planner['time_conflict_pairs'] ?? []) as $pair) {
                    $leftEventId = (int) ($pair['left_event_id'] ?? 0);
                    $rightEventId = (int) ($pair['right_event_id'] ?? 0);

                    if ($leftEventId <= 0 || $rightEventId <= 0) {
                        continue;
                    }

                    if (!isset($pairedEventByEventId[$leftEventId])) {
                        $pairedEventByEventId[$leftEventId] = $rightEventId;
                    }
                    if (!isset($pairedEventByEventId[$rightEventId])) {
                        $pairedEventByEventId[$rightEventId] = $leftEventId;
                    }
                }
                foreach ((array) $planner['items'] as $item) {
                    $eventId = (int) ($item['event_id'] ?? 0);
                    $plannerItemsByEventId[$eventId] = $item;
                    $groupKey = 'unavailable';
                    $groupLabel = 'Unavailable events';

                    if (!empty($item['is_valid']) && !empty($item['start_time'])) {
                        try {
                            $itemStart = new \DateTimeImmutable((string) $item['start_time']);
                            $groupKey = 'day-' . $itemStart->format('Y-m-d');
                            $groupLabel = $itemStart->format('l j F');
                        } catch (\Throwable $e) {
                            $groupKey = 'scheduled';
                            $groupLabel = 'Scheduled events';
                        }
                    }

                    if (!isset($groupedPlannerItems[$groupKey])) {
                        $groupedPlannerItems[$groupKey] = [
                            'label' => $groupLabel,
                            'items' => [],
                        ];
                    }

                    $eventGroupByEventId[$eventId] = $groupKey;
                    $groupedPlannerItems[$groupKey]['items'][] = $item;
                }

                $csrfToken = (string) ($csrf_token ?? '');
                $renderPlannerEventCard = static function (array $item, array $planner, string $csrfToken): void {
                ?>
                    <article id="planner-item-<?php echo (int) $item['event_id']; ?>" data-planner-event-id="<?php echo (int) $item['event_id']; ?>" class="planner-event-card<?php echo empty($item['is_valid']) ? ' is-invalid' : ''; ?>">
                        <div class="planner-event-main">
                            <div class="planner-event-title-wrap">
                                <h3 class="planner-event-title"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if (!empty($item['is_valid']) && !empty($item['venue'])): ?>
                                    <p class="planner-event-meta mb-0"><?php echo htmlspecialchars((string) $item['venue'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <?php if (empty($item['is_valid'])): ?>
                                    <p class="planner-event-invalid mb-0">This ticket is no longer available.</p>
                                <?php endif; ?>
                            </div>

                            <p class="planner-event-time mb-0">
                                <?php if (!empty($item['is_valid'])): ?>
                                    <?php echo htmlspecialchars((string) $item['time'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php else: ?>
                                    Unavailable
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="planner-event-side">
                            <div class="planner-event-pricing">
                                <strong>
                                    <?php if (!empty($item['is_valid'])): ?>
                                        &euro;<span data-planner-line-total><?php echo htmlspecialchars((string) $item['line_total'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <div class="planner-event-actions">
                                <?php if (!empty($item['is_valid']) && empty($planner['is_locked'])): ?>
                                    <form method="post" action="/planner/items/<?php echo (int) $item['event_id']; ?>/quantity" class="planner-qty-form" data-planner-async="quantity" data-planner-event-id="<?php echo (int) $item['event_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <label class="visually-hidden" for="planner-qty-<?php echo (int) $item['event_id']; ?>">Quantity for <?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></label>
                                        <input
                                            id="planner-qty-<?php echo (int) $item['event_id']; ?>"
                                            type="number"
                                            min="1"
                                            step="1"
                                            name="quantity"
                                            value="<?php echo (int) $item['quantity']; ?>"
                                            class="form-control form-control-sm"
                                            data-planner-qty-input
                                            data-auto-submit="true"
                                            required>
                                    </form>

                                    <form method="post" action="/planner/items/<?php echo (int) $item['event_id']; ?>/remove" class="m-0" data-planner-async="remove" data-planner-event-id="<?php echo (int) $item['event_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge text-bg-light border px-3 py-2">x<?php echo (int) $item['quantity']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php
                };
                ?>
                <div class="planner-layout">
                    <section class="planner-events" aria-label="Planner tickets">
                        <?php foreach ($groupedPlannerItems as $group): ?>
                            <?php $renderedEvents = []; ?>
                            <section class="planner-day-group" aria-label="<?php echo htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8'); ?>">
                                <header class="planner-day-header">
                                    <h2><?php echo htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <span class="planner-day-count"><?php echo count((array) $group['items']); ?> event<?php echo count((array) $group['items']) === 1 ? '' : 's'; ?></span>
                                </header>

                                <div class="planner-day-events">
                                    <?php foreach ((array) $group['items'] as $item): ?>
                                        <?php
                                        $eventId = (int) ($item['event_id'] ?? 0);
                                        if (isset($renderedEvents[$eventId])) {
                                            continue;
                                        }
                                        $pairedEventId = (int) ($pairedEventByEventId[$eventId] ?? 0);
                                        $isPairInSameGroup = $pairedEventId > 0
                                            && isset($plannerItemsByEventId[$pairedEventId])
                                            && (($eventGroupByEventId[$pairedEventId] ?? '') === ($eventGroupByEventId[$eventId] ?? ''))
                                            && !isset($renderedEvents[$pairedEventId]);
                                        ?>

                                        <?php if ($isPairInSameGroup): ?>
                                            <section class="planner-conflict-paired-events" data-conflict-left-event-id="<?php echo $eventId; ?>" data-conflict-right-event-id="<?php echo $pairedEventId; ?>" role="note" aria-label="Conflicting events">
                                                <h3 class="planner-conflict-paired-title">Conflicting events</h3>
                                                <?php $renderPlannerEventCard($item, $planner, $csrfToken); ?>
                                                <?php $renderPlannerEventCard((array) $plannerItemsByEventId[$pairedEventId], $planner, $csrfToken); ?>
                                            </section>
                                            <?php
                                            $renderedEvents[$eventId] = true;
                                            $renderedEvents[$pairedEventId] = true;
                                            ?>
                                        <?php else: ?>
                                            <?php $renderPlannerEventCard($item, $planner, $csrfToken); ?>
                                            <?php $renderedEvents[$eventId] = true; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </section>

                    <aside class="planner-summary-panel" aria-label="Order summary">
                        <div class="planner-summary-card">
                            <h2>Summary</h2>
                            <dl>
                                <div>
                                    <dt>Tickets</dt>
                                    <dd data-planner-count><?php echo (int) $planner['total_quantity']; ?></dd>
                                </div>
                                <div>
                                    <dt>Total</dt>
                                    <dd>&euro;<span data-planner-total><?php echo htmlspecialchars((string) $planner['total_price'], ENT_QUOTES, 'UTF-8'); ?></span></dd>
                                </div>
                            </dl>

                            <div class="planner-summary-actions">
                                <?php if (!empty($planner['is_locked'])): ?>
                                    <a href="/checkout/pending/<?php echo (int) $planner['locked_checkout_attempt_id']; ?>" class="btn cta-btn">View payment status</a>
                                <?php else: ?>
                                    <a href="/checkout" class="btn cta-btn">Proceed to checkout</a>
                                    <form method="post" action="/planner/clear" class="m-0" data-planner-async="clear">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-outline-secondary w-100">Clear tickets</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script src="/js/planner_page_async.js?v=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/../../public/js/planner_page_async.js')); ?>" defer></script>

<?php require __DIR__ . '/partials/footer.php'; ?>