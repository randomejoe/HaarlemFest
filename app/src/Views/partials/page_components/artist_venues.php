<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('artist_venues');

$componentData = is_array($data ?? null) ? $data : [];
$artistName = trim((string) ($pageContentItem->getName() ?? ''));

if ($artistName === '') {
	return;
}

$sectionId = hf_normalize_section_id($componentData['section_id'] ?? 'venues', 'venues');
$venuesTitle = hf_data($componentData, 'venues_title', 'Venue Information');
$venuesSubtitleTemplate = hf_data($componentData, 'venues_subtitle', "Where you'll see {artist_name} live");
$venuesSubtitle = str_replace('{artist_name}', $artistName, $venuesSubtitleTemplate);
$mapTitle = hf_data($componentData, 'map_title', 'Festival Locations');
$mapImage = hf_data($componentData, 'map_image');
$mapImageAlt = hf_data($componentData, 'map_image_alt', 'Festival map with venue locations');
$mapImageUrl = hf_image_url($mapImage);

?>

<section class="avn-venues" id="<?php echo hf_e($sectionId); ?>">
	<div class="avn-shell avn-grid">
		<div class="avn-info">
			<h2><?php echo hf_e($venuesTitle); ?></h2>
			<?php if ($venuesSubtitle !== ''): ?>
				<p class="avn-subtitle"><?php echo hf_e($venuesSubtitle); ?></p>
			<?php endif; ?>

			<?php if ($data['artistVenues'] !== []): ?>
				<?php foreach ($data['artistVenues'] as $venue): ?>
					<article class="avn-venue-card">
						<div class="avn-venue-head">
							<div>
								<h3><?php echo hf_e($venue['name'] ?? 'Venue to be announced'); ?></h3>
								<?php if (!empty($venue['stage_label'])): ?>
									<p><?php echo hf_e($venue['stage_label']); ?></p>
								<?php endif; ?>
							</div>

							<?php if (!empty($venue['capacity_label'])): ?>
								<div class="avn-capacity" aria-label="Venue capacity">
									<span class="avn-capacity-dot" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
											<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
											<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5a7.5 7.5 0 0 1 15 0"></path>
										</svg>
									</span>
									<span><?php echo hf_e($venue['capacity_label']); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<?php if (!empty($venue['description'])): ?>
							<p class="avn-description"><?php echo hf_e($venue['description']); ?></p>
						<?php endif; ?>

						<div class="avn-details">
							<?php if (!empty($venue['address'])): ?>
								<p>
									<span class="avn-detail-icon" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z"></path>
											<circle cx="12" cy="10" r="2.5"></circle>
										</svg>
									</span>
									<span><?php echo hf_e($venue['address']); ?></span>
								</p>
							<?php endif; ?>

							<?php if (!empty($venue['facilities'])): ?>
								<p>
									<span class="avn-detail-icon avn-detail-icon-alt" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
											<circle cx="12" cy="12" r="9"></circle>
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4"></path>
											<circle cx="12" cy="16" r="0.8" fill="currentColor" stroke="none"></circle>
										</svg>
									</span>
									<span><?php echo hf_e($venue['facilities']); ?></span>
								</p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			<?php else: ?>
				<div class="avn-empty-state">Venue information for <?php echo hf_e($artistName); ?> will be announced soon.</div>
			<?php endif; ?>
		</div>

		<div class="avn-map-wrap">
			<h2><?php echo hf_e($mapTitle); ?></h2>
			<div class="avn-map-card">
				<?php if ($mapImageUrl !== ''): ?>
					<img
						class="avn-map-image"
						src="<?php echo hf_e($mapImageUrl); ?>"
						alt="<?php echo hf_e($mapImageAlt); ?>"
						loading="lazy">
				<?php else: ?>
					<div class="avn-map-placeholder">Upload the festival map image in CMS</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>