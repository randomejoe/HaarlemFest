<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('artist_schedule');

$componentData = is_array($data ?? null) ? $data : [];
$artistName = trim((string) ($pageContentItem['title'] ?? ''));

if ($artistName === '') {
	return;
}

$sectionId = hf_normalize_section_id($componentData['section_id'] ?? 'schedule', 'schedule');
$titleId = $sectionId . '-title';
$ticketsCtaUrl = hf_data($componentData, 'tickets_cta_url', '/jazz');
$scheduleEvents = [];

if (isset($eventService) && method_exists($eventService, 'getArtistScheduleData')) {
	$scheduleEvents = $eventService->getArtistScheduleData($artistName);
}

$plannableEventIds = [];
foreach ($scheduleEvents as $scheduleEvent) {
	if (!empty($scheduleEvent['can_add_to_planner'])) {
		$plannableEventIds[] = (int) $scheduleEvent['id'];
	}
}

$returnTo = (string) ($_SERVER['REQUEST_URI'] ?? '/jazz');
if ($returnTo === '' || $returnTo[0] !== '/') {
	$returnTo = '/jazz';
}

$selectionFormId = 'ars-add-form-' . preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($sectionId));
?>

<section class="ars-schedule-section" id="<?php echo hf_e($sectionId); ?>" aria-labelledby="<?php echo hf_e($titleId); ?>">
	<div class="ars-shell">
		<div class="ars-section-heading">
			<span class="ars-section-heading-line" aria-hidden="true"></span>
			<h2 id="<?php echo hf_e($titleId); ?>">Where &amp; When to See <?php echo hf_e($artistName); ?></h2>
			<span class="ars-section-heading-line" aria-hidden="true"></span>
		</div>

		<?php if ($scheduleEvents !== []): ?>
			<div class="ars-table-wrap">
				<div class="ars-table" role="table" aria-label="Artist schedule">
					<div class="ars-table-header" role="row">
						<span>Day &amp; Date</span>
						<span>Time</span>
						<span>Venue</span>
						<span>Event type</span>
						<span>Tickets</span>
					</div>

					<?php $hasDefaultSelectedEvent = false; ?>
					<?php foreach ($scheduleEvents as $scheduleEvent): ?>
						<?php
						$canSelect = !empty($scheduleEvent['can_add_to_planner']);
						$isSelected = $canSelect && !$hasDefaultSelectedEvent;
						if ($isSelected) {
							$hasDefaultSelectedEvent = true;
						}
						$rowClasses = 'ars-table-row';
						if (!empty($scheduleEvent['is_highlighted'])) {
							$rowClasses .= ' is-highlighted';
						}
						if ($canSelect) {
							$rowClasses .= ' is-selectable';
							if ($isSelected) {
								$rowClasses .= ' is-selected';
							}
						} else {
							$rowClasses .= ' is-unselectable';
						}
						?>
						<article
							class="<?php echo hf_e($rowClasses); ?>"
							role="row"
							<?php echo $canSelect ? 'data-event-selectable="true" data-event-id="' . (int) $scheduleEvent['id'] . '"' : ''; ?>>
							<div class="ars-table-cell ars-table-day<?php echo !empty($scheduleEvent['is_free']) ? ' is-free' : ''; ?>">
								<div>
									<?php if ($canSelect): ?>
										<input
											type="checkbox"
											class="ars-event-selector"
											name="event_ids[]"
											value="<?php echo (int) $scheduleEvent['id']; ?>"
											form="<?php echo hf_e($selectionFormId); ?>"
											<?php echo $isSelected ? 'checked' : ''; ?>>
									<?php endif; ?>
									<p>
										<?php echo hf_e($scheduleEvent['day_label']); ?>
										<?php if ($scheduleEvent['badge_label'] !== ''): ?>
											<span class="ars-pill <?php echo hf_e($scheduleEvent['badge_class']); ?>"><?php echo hf_e($scheduleEvent['badge_label']); ?></span>
										<?php endif; ?>
									</p>
									<strong><?php echo hf_e($scheduleEvent['date_label']); ?></strong>
								</div>
							</div>
							<div class="ars-table-cell">
								<strong class="ars-gold-text"><?php echo hf_e($scheduleEvent['time_label']); ?></strong>
							</div>
							<div class="ars-table-cell">
								<strong><?php echo hf_e($scheduleEvent['venue_label']); ?></strong>
								<?php if ($scheduleEvent['location_label'] !== ''): ?>
									<span><?php echo hf_e($scheduleEvent['location_label']); ?></span>
								<?php endif; ?>
							</div>
							<div class="ars-table-cell">
								<span><?php echo hf_e($scheduleEvent['event_type']); ?></span>
							</div>
							<div class="ars-table-cell">
								<span class="ars-ticket-state <?php echo hf_e($scheduleEvent['tickets_class']); ?>"><?php echo hf_e($scheduleEvent['tickets_label']); ?></span>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php else: ?>
			<div class="ars-empty-state">
				Schedule details for <?php echo hf_e($artistName); ?> will be announced soon.
			</div>
		<?php endif; ?>

		<div class="ars-schedule-panels">
			<article class="ticket-panel" id="tickets">
				<p class="ars-panel-eyebrow">Ticket Prices</p>
				<div class="ars-ticket-list" role="list">
					<div class="ars-ticket-list-item" role="listitem">
						<span>Regular ticket (single show)</span>
						<strong>&euro;10</strong>
					</div>
					<div class="ars-ticket-list-item" role="listitem">
						<span>Premium ticket (select shows)</span>
						<strong>&euro;15</strong>
					</div>
					<div class="ars-ticket-list-item" role="listitem">
						<span>All-access Day Pass</span>
						<strong>&euro;35</strong>
					</div>
					<div class="ars-ticket-list-item ars-ticket-list-item-plain" role="listitem">
						<span>All-access 3-Day Pass</span>
						<strong>&euro;80</strong>
					</div>
				</div>
				<ul class="ars-ticket-notes">
					<li>Regular/Day Pass tickets apply to club sessions at Het Patronaat</li>
					<li>Sunday shows at Grote Markt are completely free</li>
				</ul>
			</article>

			<article class="cta-panel">
				<h3>Ready to experience <?php echo hf_e($artistName); ?> live?</h3>
				<p>Add this show to your personal programme or go straight to tickets to secure your spot.</p>
				<div class="ars-button-row">
					<form method="post" action="/planner/items" class="ars-add-form" id="<?php echo hf_e($selectionFormId); ?>">
						<input type="hidden" name="csrf_token" value="<?php echo hf_e(hf_csrf_token()); ?>">
						<input type="hidden" name="quantity" value="1">
						<input type="hidden" name="return_to" value="<?php echo hf_e($returnTo); ?>">
						<button
							type="submit"
							class="ars-button ars-button-gold hf-planner-submit-btn"
							data-adding-label="Adding..."
							data-submit-delay-ms="650"
							<?php echo $plannableEventIds !== [] ? '' : 'disabled'; ?>>
							<span class="ars-button-spinner" aria-hidden="true"></span>
							<span class="ars-button-label">Add to My Programme</span>
						</button>
					</form>
					<a class="ars-button ars-button-outline" href="<?php echo hf_e($ticketsCtaUrl); ?>">Go to Tickets</a>
				</div>
			</article>
		</div>
	</div>
</section>
<script>
	(() => {
		const roots = document.querySelectorAll('.ars-schedule-section');

		roots.forEach((root) => {
			if (root.dataset.bound === 'true') {
				return;
			}

			root.dataset.bound = 'true';

			const form = root.querySelector('.ars-add-form');
			const submitButton = form ? form.querySelector('button[type="submit"]') : null;
			const selectableRows = Array.from(root.querySelectorAll('[data-event-selectable="true"]'));

			const updateSelectionState = () => {
				let selectedCount = 0;

				selectableRows.forEach((row) => {
					const checkbox = row.querySelector('.ars-event-selector');
					const isSelected = !!(checkbox && checkbox.checked);
					row.classList.toggle('is-selected', isSelected);
					if (isSelected) {
						selectedCount += 1;
					}
				});

				if (submitButton) {
					submitButton.disabled = selectedCount === 0;
				}
			};

			selectableRows.forEach((row) => {
				const checkbox = row.querySelector('.ars-event-selector');
				if (!checkbox) {
					return;
				}

				row.addEventListener('click', (event) => {
					if (event.target instanceof HTMLElement && event.target.closest('a, button, input, label')) {
						return;
					}

					checkbox.checked = !checkbox.checked;
					updateSelectionState();
				});

				checkbox.addEventListener('change', updateSelectionState);
			});

			updateSelectionState();
		});
	})();
</script>
<script src="/js/planner_async.js?v=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/../../../../public/js/planner_async.js')); ?>" defer></script>