<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('tickets_passes');

$sectionId = hf_normalize_section_id($data['section_id'] ?? 'tickets', 'tickets');

$heading = hf_data($data, 'heading');
$introText = hf_data($data, 'intro_text');

$cardFields = ['title', 'price', 'description', 'cta_label', 'cta_url', 'badge'];

$cards = [];
foreach (range(1, 3) as $i) {
	$card = [];
	foreach ($cardFields as $field) {
		$card[$field] = hf_data($data, "card_{$i}_{$field}");
	}
	$cards[] = $card;
}

$notes = array_values(array_filter([
	hf_data($data, 'note_1'),
	hf_data($data, 'note_2'),
], static fn(string $note): bool => $note !== ''));
?>
<section class="tp-tickets" id="<?php echo hf_e($sectionId); ?>">
	<div class="tp-panel">
		<div class="tp-divider">
			<span><?php echo hf_e($heading); ?></span>
		</div>

		<?php if ($introText !== ''): ?>
			<p class="tp-copy"><?php echo hf_e($introText); ?></p>
		<?php endif; ?>

		<div class="tp-grid">
			<?php foreach ($cards as $card): ?>
				<?php
				$hasBadge = $card['badge'] !== '';
				$cardClass = $hasBadge ? ' tp-card-highlighted' : '';
				$ctaUrl = $card['cta_url'] !== '' ? $card['cta_url'] : '#';
				?>
				<article class="tp-card<?php echo $cardClass; ?>">
					<?php if ($hasBadge): ?>
						<span class="tp-badge"><?php echo hf_e($card['badge']); ?></span>
					<?php endif; ?>

					<h3><?php echo hf_e($card['title']); ?></h3>
					<span class="tp-price"><?php echo hf_e($card['price']); ?></span>
					<p><?php echo hf_e($card['description']); ?></p>
					<a class="tp-button" href="<?php echo hf_e($ctaUrl); ?>"><?php echo hf_e($card['cta_label']); ?></a>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ($notes !== []): ?>
			<div class="tp-notes">
				<?php foreach ($notes as $note): ?>
					<p>&bull; <?php echo hf_e($note); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>