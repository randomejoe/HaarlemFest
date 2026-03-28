<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('artist_listening');

$componentData = is_array($data ?? null) ? $data : [];

$sectionId = hf_normalize_section_id($componentData['section_id'] ?? 'listen', 'listen');
$sectionTitle = hf_data($componentData, 'section_title');
$titleId = $sectionId . '-title';

$previewLabel = 'Preview';
$featuredLabel = 'Featured at Haarlem Jazz';
$buttonLabel = 'Play Preview';

$cards = [];
foreach (range(1, 4) as $index) {
	$image = hf_data($componentData, 'card_' . $index . '_image');
	$imageUrl = hf_image_url($image);
	if ($imageUrl === '') {
		continue;
	}

	$previewRaw = strtolower(hf_data($componentData, 'card_' . $index . '_preview'));
	$featuredRaw = strtolower(hf_data($componentData, 'card_' . $index . '_featured'));
	$cards[] = [
		'image_url' => $imageUrl,
		'image_alt' => hf_data($componentData, 'card_' . $index . '_image_alt'),
		'preview' => in_array($previewRaw, ['1', 'true', 'yes', 'y', 'on'], true),
		'badge' => hf_data($componentData, 'card_' . $index . '_badge'),
		'tracks_label' => hf_data($componentData, 'card_' . $index . '_tracks_label'),
		'year_label' => hf_data($componentData, 'card_' . $index . '_year_label'),
		'title' => hf_data($componentData, 'card_' . $index . '_title'),
		'description' => hf_data($componentData, 'card_' . $index . '_description'),
		'featured' => in_array($featuredRaw, ['1', 'true', 'yes', 'y', 'on'], true),
	];
}

if ($cards === []) {
	return;
}
?>

<section class="al-listening" id="<?php echo hf_e($sectionId); ?>" <?php echo $sectionTitle !== '' ? ' aria-labelledby="' . hf_e($titleId) . '"' : ''; ?>>
	<div class="al-shell">
		<?php if ($sectionTitle !== ''): ?>
			<div class="al-section-heading">
				<span class="al-section-heading-line" aria-hidden="true"></span>
				<h2 id="<?php echo hf_e($titleId); ?>"><?php echo hf_e($sectionTitle); ?></h2>
				<span class="al-section-heading-line" aria-hidden="true"></span>
			</div>
		<?php endif; ?>

		<div class="al-grid">
			<?php foreach ($cards as $card): ?>
				<?php
				$hasBadges = $card['preview'] || $card['badge'] !== '';
				$badgeWrapClass = $card['preview'] || $card['badge'] === '' ? '' : ' al-cover-badges-single';
				$hasMeta = $card['tracks_label'] !== '' || $card['year_label'] !== '';
				?>
				<article class="al-card">
					<div class="al-card-cover">
						<img src="<?php echo hf_e($card['image_url']); ?>" alt="<?php echo hf_e($card['image_alt']); ?>" loading="lazy">
						<?php if ($hasBadges): ?>
							<div class="al-cover-badges<?php echo hf_e($badgeWrapClass); ?>">
								<?php if ($card['preview']): ?>
									<span class="al-cover-badge al-cover-badge-gold"><?php echo hf_e($previewLabel); ?></span>
								<?php endif; ?>
								<?php if ($card['badge'] !== ''): ?>
									<span class="al-cover-badge"><?php echo hf_e($card['badge']); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ($hasMeta): ?>
							<div class="al-cover-meta">
								<?php if ($card['tracks_label'] !== ''): ?>
									<span><?php echo hf_e($card['tracks_label']); ?></span>
								<?php endif; ?>
								<?php if ($card['year_label'] !== ''): ?>
									<strong><?php echo hf_e($card['year_label']); ?></strong>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="al-card-copy">
						<?php if ($card['title'] !== ''): ?>
							<h3><?php echo hf_e($card['title']); ?></h3>
						<?php endif; ?>
						<?php if ($card['description'] !== ''): ?>
							<p><?php echo hf_e($card['description']); ?></p>
						<?php endif; ?>
						<?php if ($card['featured']): ?>
							<p class="al-featured-note"><span aria-hidden="true"></span><?php echo hf_e($featuredLabel); ?></p>
						<?php endif; ?>
					</div>

					<button type="button" class="al-card-button"><?php echo hf_e($buttonLabel); ?></button>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>