<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('venues_map');

$sectionId = hf_normalize_section_id($data['section_id'] ?? 'venues', 'venues');

$heading = hf_data($data, 'heading');
$introText = hf_data($data, 'intro_text');
$mapImage = hf_data($data, 'map_image');
$mapImageAlt = hf_data($data, 'map_image_alt');

$mapImageUrl = hf_image_url($mapImage);

$locationFields = ['name', 'address', 'description'];

$locations = [];
foreach (range(1, 2) as $i) {
	$location = [];
	foreach ($locationFields as $field) {
		$location[$field] = hf_data($data, "location_{$i}_{$field}");
	}
	$locations[] = $location;
}

$locations = array_values(array_filter($locations, static fn(array $location): bool =>
$location['name'] !== '' || $location['address'] !== '' || $location['description'] !== ''));
?>
<section class="vm-venues" id="<?php echo hf_e($sectionId); ?>">
	<div class="vm-divider">
		<span><?php echo hf_e($heading); ?></span>
	</div>

	<?php if ($introText !== ''): ?>
		<p class="vm-copy"><?php echo hf_e($introText); ?></p>
	<?php endif; ?>

	<div class="vm-map-frame">
		<?php if ($mapImageUrl !== ''): ?>
			<img
				class="vm-map-image"
				src="<?php echo hf_e($mapImageUrl); ?>"
				alt="<?php echo hf_e($mapImageAlt); ?>">
		<?php else: ?>
			<div class="vm-map-placeholder">Upload a map image in CMS</div>
		<?php endif; ?>
	</div>

	<?php if ($locations !== []): ?>
		<div class="vm-location-grid">
			<?php foreach ($locations as $location): ?>
				<article class="vm-location-card">
					<?php if ($location['name'] !== ''): ?>
						<h3><?php echo hf_e($location['name']); ?></h3>
					<?php endif; ?>

					<?php if ($location['address'] !== ''): ?>
						<p class="vm-location-address"><?php echo hf_e($location['address']); ?></p>
					<?php endif; ?>

					<?php if ($location['description'] !== ''): ?>
						<p class="vm-location-description"><?php echo hf_e($location['description']); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>