<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('jazz_program');

// Fetch program data — all days with full event details for client-side tab switching
$jazzDays = [];
if (isset($eventService)) {
	$jazzDays = $eventService->getProgramDataForCategory('jazz');
}

// Planner state for the sticky bar at the bottom of the overlay
$jazzPlannerCount  = 0;
$jazzPlannerTotal  = '0.00';
$jazzPlannerFlash  = null;
if (isset($plannerService)) {
	$jazzPlannerDetails = $plannerService->getDetailedPlanner();
	$jazzPlannerCount   = (int) $jazzPlannerDetails['total_quantity'];
	$jazzPlannerTotal   = (string) $jazzPlannerDetails['total_price'];
	$jazzPlannerFlash   = $plannerService->consumeFlash();
}

// After a planner POST, the PlannerController redirects here.
// Appending #jazz-open tells jazz_program.js to auto-open the overlay on load.
$jazzReturnTo = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') . '#jazz-open';
?>
<div
	id="jazz-program-overlay"
	class="jazz-program-overlay"
	hidden
	role="dialog"
	aria-modal="true"
	aria-label="Jazz Festival Program"
	tabindex="-1">
	<header class="jazz-overlay-header">
		<div class="jazz-overlay-header-inner container">
			<button type="button" class="jazz-back-btn" data-jazz-close aria-label="Back to page">
				Back
			</button>
			<h2 class="jazz-overlay-title">Festival Program by Day</h2>
			<button type="button" class="jazz-overlay-close" data-jazz-close aria-label="Close jazz program">
				&times;
			</button>
		</div>
	</header>

	<div class="jazz-overlay-body">
		<?php if (!empty($jazzDays)): ?>
			<div class="jazz-tabs-wrap">
				<div class="container">
					<div class="jazz-day-tabs" role="tablist" aria-label="Festival days">
						<?php foreach ($jazzDays as $i => $day): ?>
							<button
								type="button"
								class="jazz-day-tab<?php echo $i === 0 ? ' active' : ''; ?>"
								data-jazz-tab="<?php echo hf_e($day['key']); ?>"
								role="tab"
								aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
								<span class="jazz-day-tab-day"><?php echo hf_e($day['label_day']); ?></span>
								<span class="jazz-day-tab-date"><?php echo hf_e($day['label_date']); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<section class="jazz-program">
			<div class="container">
				<?php if (!empty($jazzPlannerFlash['message'])): ?>
					<p class="account-message <?php echo hf_e((string) ($jazzPlannerFlash['type'] ?? 'info')); ?>">
						<?php echo hf_e((string) $jazzPlannerFlash['message']); ?>
					</p>
				<?php endif; ?>

				<?php if (empty($jazzDays)): ?>
					<div class="jazz-empty-state">No Jazz events are available yet.</div>
				<?php else: ?>
					<?php foreach ($jazzDays as $i => $day): ?>
						<div
							data-jazz-panel="<?php echo hf_e($day['key']); ?>"
							<?php echo $i > 0 ? 'hidden' : ''; ?>>
							<?php if (empty($day['events'])): ?>
								<div class="jazz-empty-state">No Jazz events available for this day.</div>
							<?php else: ?>
								<div class="row g-4 jazz-program-grid">
									<?php foreach ($day['events'] as $event): ?>
										<div class="col-12 col-xl-6">
											<article class="jazz-event-card h-100">
												<h3><?php echo hf_e($event['name']); ?></h3>
												<ul class="jazz-event-meta">
													<li><?php echo hf_e($event['time']); ?></li>
													<li><?php echo hf_e((string) ($event['venue'] ?? '')); ?></li>
												</ul>
												<p class="jazz-event-description"><?php echo hf_e((string) ($event['description'] ?? '')); ?></p>
												<div class="jazz-event-summary<?php echo empty($event['availability_label']) ? ' no-availability' : ''; ?>">
													<?php if (!empty($event['availability_label'])): ?>
														<p class="jazz-availability mb-0"><?php echo hf_e((string) $event['availability_label']); ?></p>
													<?php endif; ?>
													<div class="jazz-price-wrap<?php echo empty($event['status']) ? ' price-only' : ''; ?>">
														<?php if (!empty($event['status'])): ?>
															<span class="jazz-status <?php echo hf_e((string) $event['status_class']); ?>">
																<?php echo hf_e((string) $event['status']); ?>
															</span>
														<?php endif; ?>
														<span class="jazz-price<?php echo !empty($event['is_free']) ? ' is-free' : ''; ?>">
															<?php echo !empty($event['is_free']) ? 'FREE' : '&euro;' . hf_e($event['price']); ?>
														</span>
													</div>
												</div>
												<?php if (!empty($event['can_add_to_planner'])): ?>
													<div class="jazz-card-actions">
														<form method="post" action="/planner/items" class="jazz-add-form">
															<input type="hidden" name="csrf_token" value="<?php echo hf_e(hf_csrf_token()); ?>">
															<input type="hidden" name="event_id" value="<?php echo (int) $event['event_id']; ?>">
															<input type="hidden" name="return_to" value="<?php echo hf_e($jazzReturnTo); ?>">
															<div class="jazz-qty" aria-label="Quantity selector">
																<label for="jazz-qty-<?php echo (int) $event['event_id']; ?>" class="visually-hidden">Quantity</label>
																<input
																	id="jazz-qty-<?php echo (int) $event['event_id']; ?>"
																	class="jazz-qty-input"
																	type="number"
																	min="1"
																	<?php if ($event['seat_count'] !== null): ?>max="<?php echo max(1, (int) $event['seat_count']); ?>" <?php endif; ?>
																	step="1"
																	name="quantity"
																	value="1"
																	required>
															</div>
															<button
																type="submit"
																class="jazz-add-btn hf-planner-submit-btn"
																data-adding-label="Adding..."
																data-submit-delay-ms="650"
																>
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
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>
	</div>

	<section class="jazz-planner-bar">
		<div class="jazz-planner-inner container">
			<div class="jazz-planner-copy">
				<h2>Your Personal Planner</h2>
				<p><span data-planner-count><?php echo (int) $jazzPlannerCount; ?></span> <span data-planner-item-label><?php echo (int) $jazzPlannerCount === 1 ? 'item' : 'items'; ?></span> &bull; Total: &euro;<span data-planner-total><?php echo hf_e($jazzPlannerTotal); ?></span></p>
			</div>
			<div class="jazz-planner-actions">
				<a href="/planner" class="jazz-outline-btn">View Planner</a>
				<a href="/checkout" class="jazz-primary-btn">Proceed to Checkout</a>
			</div>
		</div>
	</section>
</div>

<script src="/js/planner_async.js?v=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/../../../../public/js/planner_async.js')); ?>" defer></script>
<script src="/js/jazz_program.js?v=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/../../../../public/js/jazz_program.js')); ?>" defer></script>
