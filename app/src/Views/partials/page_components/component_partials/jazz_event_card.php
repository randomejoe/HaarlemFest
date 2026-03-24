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
			<?php $maxQuantity = $event['seat_count'] !== null ? max(1, (int) $event['seat_count']) : null; ?>
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
						<?php if ($maxQuantity !== null): ?>max="<?php echo $maxQuantity; ?>" <?php endif; ?>
						step="1"
						name="quantity"
						value="1"
						required>
				</div>

				<button type="submit" class="jazz-add-btn" <?php echo !empty($planner_locked) ? 'disabled' : ''; ?>>
					Add to Planner
				</button>
			</form>
		</div>
	<?php endif; ?>
</article>