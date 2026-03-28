<?php
$plannerData = is_array($planner ?? null) ? $planner : [];
$conflicts = (array) ($plannerData['time_conflicts'] ?? []);

if ($conflicts === []) {
	return;
}

$pairs = (array) ($plannerData['time_conflict_pairs'] ?? []);
$maxFallback = 3;
?>

<section class="planner-conflicts mb-4" role="alert" aria-live="polite">
	<div class="planner-conflicts-head">
		<h2>Conflicting events</h2>
	</div>

	<?php if ($pairs !== []): ?>
		<ul class="planner-conflict-list">
			<?php foreach ($pairs as $pair): ?>
				<li class="planner-conflict-item" data-conflict-left-event-id="<?php echo (int) ($pair['left_event_id'] ?? 0); ?>" data-conflict-right-event-id="<?php echo (int) ($pair['right_event_id'] ?? 0); ?>">
					<p class="planner-conflict-copy mb-0"><?php echo htmlspecialchars((string) ($pair['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<ul class="planner-conflict-list">
			<?php
			$shown = 0;
			foreach ($conflicts as $conflict):
				if ($shown >= $maxFallback) {
					break;
				}
				$shown++;
			?>
				<li class="planner-conflict-item">
					<p class="planner-conflict-copy mb-0"><?php echo htmlspecialchars((string) $conflict, ENT_QUOTES, 'UTF-8'); ?></p>
				</li>
			<?php endforeach; ?>
			<?php if (count($conflicts) > $maxFallback): ?>
				<li class="planner-conflict-item">
					<p class="planner-conflict-copy mb-0 fst-italic"><?php echo htmlspecialchars('And more...', ENT_QUOTES, 'UTF-8'); ?></p>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>
</section>