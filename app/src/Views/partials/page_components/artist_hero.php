<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('artist_hero');

$artistName = hf_data($data ?? [], 'artist_name');
$artistSummary = hf_data($data ?? [], 'artist_summary');
$artistLocation = hf_data($data ?? [], 'artist_location');
$artistGenres = hf_data($data ?? [], 'artist_genres');
$featuredEventId = (int) hf_data($data ?? [], 'featured_event_id', '0');
$featuredEventNote = hf_data($data ?? [], 'featured_event_note');
$ticketsCtaLabel = hf_data($data ?? [], 'tickets_cta_label', 'Tickets & Schedule');
$ticketsCtaUrl = hf_data($data ?? [], 'tickets_cta_url', '/jazz');
$artistImage = hf_data($data ?? [], 'artist_image');
$artistImageAlt = hf_data($data ?? [], 'artist_image_alt', 'Artist image');

$featuredEvent = $data['featuredEvent'] ?? null;

if ($featuredEvent !== null && $artistName === '') {
	$artistName = $featuredEvent->getName();
}

if ($featuredEvent !== null && $artistImage === '') {
	$artistImage = (string) ($featuredEvent->artistImg() ?? '');
}

$artistImageUrl = hf_image_url($artistImage);
$showDay = $featuredEvent ? $featuredEvent->startsAt()->format('l') : 'TBA';
$showDate = $featuredEvent ? $featuredEvent->startsAt()->format('d F') : 'Date TBA';
$showTime = $featuredEvent ? $featuredEvent->startsAt()->format('H:i') . '-' . $featuredEvent->endsAt()->format('H:i') : 'Time TBA';

$showVenue = 'Venue to be announced';
if ($featuredEvent !== null) {
	$venue = trim((string) ($featuredEvent->venue() ?? ''));
	$location = trim((string) ($featuredEvent->location() ?? ''));

	if ($venue !== '' && $location !== '' && strcasecmp($venue, $location) !== 0) {
		$showVenue = $venue . ' · ' . $location;
	} elseif ($venue !== '') {
		$showVenue = $venue;
	} elseif ($location !== '') {
		$showVenue = $location;
	}
}

$canAddToProgram = $featuredEvent !== null && $featuredEvent->canBePlanned();
$returnTo = (string) ($_SERVER['REQUEST_URI'] ?? '/jazz');
if ($returnTo === '' || $returnTo[0] !== '/') {
	$returnTo = '/jazz';
}
?>

<section class="ah-artist-hero" aria-labelledby="ah-artist-title">
	<div class="ah-shell">
		<div class="ah-content">
			<a class="ah-back-link" href="/jazz">
				<span aria-hidden="true">&larr;</span>
				<span>Back to Haarlem jazz</span>
			</a>

			<p class="ah-eyebrow">Event <span aria-hidden="true">&middot;</span> Haarlem Jazz</p>

			<h1 id="ah-artist-title" class="ah-title"><?php echo hf_e($artistName); ?></h1>

			<?php if ($artistSummary !== ''): ?>
				<p class="ah-summary"><?php echo hf_e($artistSummary); ?></p>
			<?php endif; ?>

			<div class="ah-tags" aria-label="Artist details">
				<?php if ($artistLocation !== ''): ?>
					<p class="ah-tag">
						<span class="ah-tag-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
								<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 12-7.5 12S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
							</svg>
						</span>
						<span><?php echo hf_e($artistLocation); ?></span>
					</p>
				<?php endif; ?>
				<?php if ($artistGenres !== ''): ?>
					<p class="ah-tag">
						<span class="ah-tag-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9 18.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm10.5-3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"></path>
								<path stroke-linecap="round" stroke-linejoin="round" d="M9 18.75V5.25l10.5-2.25v12.75"></path>
							</svg>
						</span>
						<span><?php echo hf_e($artistGenres); ?></span>
					</p>
				<?php endif; ?>
			</div>

			<article class="ah-show-card" aria-label="Featured event">
				<p class="ah-show-eyebrow">Next show at The Festival.</p>
				<div class="ah-show-meta">
					<strong><?php echo hf_e($showDay); ?></strong>
					<strong><?php echo hf_e($showDate); ?></strong>
					<span><?php echo hf_e($showTime); ?></span>
				</div>
				<p class="ah-show-venue"><?php echo hf_e($showVenue); ?></p>
				<?php if ($featuredEventNote !== ''): ?>
					<p class="ah-show-extra"><?php echo hf_e($featuredEventNote); ?></p>
				<?php endif; ?>
			</article>

			<div class="ah-actions">
				<form method="post" action="/planner/items" class="ah-add-form">
					<input type="hidden" name="csrf_token" value="<?php echo hf_e(hf_csrf_token()); ?>">
					<input type="hidden" name="event_id" value="<?php echo (int) $featuredEventId; ?>">
					<input type="hidden" name="quantity" value="1">
					<input type="hidden" name="return_to" value="<?php echo hf_e($returnTo); ?>">
					<button
						type="submit"
						class="ah-btn ah-btn-gold hf-planner-submit-btn"
						data-adding-label="Adding..."
						data-submit-delay-ms="650"
						<?php echo $canAddToProgram ? '' : 'disabled'; ?>>
						<span class="ah-btn-spinner" aria-hidden="true"></span>
						<span class="ah-btn-label">Add to My Programme</span>
					</button>
				</form>
				<a class="ah-btn ah-btn-outline" href="<?php echo hf_e($ticketsCtaUrl); ?>"><?php echo hf_e($ticketsCtaLabel); ?></a>
			</div>
		</div>

		<figure class="ah-visual">
			<?php if ($artistImageUrl !== ''): ?>
				<img src="<?php echo hf_e($artistImageUrl); ?>" alt="<?php echo hf_e($artistImageAlt); ?>">
			<?php endif; ?>
		</figure>
	</div>
</section>
<script src="/js/planner_async.js?v=<?php echo hf_asset_version(__DIR__ . '/../../../../public/js/planner_async.js'); ?>" defer></script>