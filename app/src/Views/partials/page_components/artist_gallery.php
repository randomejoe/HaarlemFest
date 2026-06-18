<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('artist_gallery');

$componentData = is_array($data ?? null) ? $data : [];

$sectionId = hf_normalize_section_id($componentData['section_id'] ?? 'artist-media-gallery', 'artist-media-gallery');

$cards = [
	[
		'image' => hf_data($componentData, 'card_1_image'),
		'alt' => hf_data($componentData, 'card_1_image_alt'),
		'caption' => hf_data($componentData, 'card_1_caption'),
		'class' => 'ag-gallery-card-performance',
	],
	[
		'image' => hf_data($componentData, 'card_2_image'),
		'alt' => hf_data($componentData, 'card_2_image_alt'),
		'caption' => hf_data($componentData, 'card_2_caption'),
		'class' => 'ag-gallery-card-portrait ag-gallery-card-light',
	],
];

$renderableCards = [];
foreach ($cards as $card) {
	$imageUrl = hf_image_url($card['image']);
	if ($imageUrl === '') {
		continue;
	}

	$card['imageUrl'] = $imageUrl;
	$renderableCards[] = $card;
}

if ($renderableCards === []) {
	return;
}
?>

<section class="ag-artist-gallery" id="<?php echo hf_e($sectionId); ?>">
	<div class="ag-shell ag-grid">
		<?php foreach ($renderableCards as $card): ?>
			<figure class="ag-gallery-card <?php echo hf_e($card['class']); ?>">
				<img src="<?php echo hf_e($card['imageUrl']); ?>" alt="<?php echo hf_e($card['alt']); ?>" loading="lazy">
				<?php if ($card['caption'] !== ''): ?>
					<figcaption><?php echo hf_e($card['caption']); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endforeach; ?>
	</div>
</section>