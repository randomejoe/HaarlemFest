<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('hero_banner');

$backgroundImage = hf_data($data, 'background_image');
$backgroundImageUrl = hf_image_url($backgroundImage);

$dateBadge = hf_data($data, 'date_badge');
$heading = hf_data($data, 'heading');
$subheading = hf_data($data, 'subheading');

$primaryCtaLabel = hf_data($data, 'primary_cta_label');
$primaryCtaUrl = hf_data($data, 'primary_cta_url');
$secondaryCtaLabel = hf_data($data, 'secondary_cta_label');
$secondaryCtaUrl = hf_data($data, 'secondary_cta_url');

$scrollTarget = hf_normalize_section_id(ltrim((string)($data['scroll_target'] ?? ''), '#'), 'intro');

$sectionStyle = '';
if ($backgroundImageUrl !== '') {
	$sectionStyle = "--hf-hero-image: url('" . hf_e($backgroundImageUrl) . "');";
}

$ctas = [
	['label' => $primaryCtaLabel, 'url' => $primaryCtaUrl, 'variant' => 'primary'],
	['label' => $secondaryCtaLabel, 'url' => $secondaryCtaUrl, 'variant' => 'secondary'],
];

$ctas = array_values(array_filter($ctas, static fn(array $cta): bool =>
$cta['label'] !== '' && $cta['url'] !== ''));
?>

<section class="hf-hero" <?php echo $sectionStyle !== '' ? ' style="' . $sectionStyle . '"' : ''; ?>>
	<div class="hf-hero__shell">
		<?php if ($dateBadge !== ''): ?>
			<p class="hf-hero__date"><?php echo hf_e($dateBadge); ?></p>
		<?php endif; ?>

		<h1 class="hf-hero__heading"><?php echo hf_e($heading); ?></h1>

		<?php if ($subheading !== ''): ?>
			<p class="hf-hero__subheading"><?php echo hf_e($subheading); ?></p>
		<?php endif; ?>

		<?php if ($ctas !== []): ?>
			<div class="hf-hero__actions">
				<?php foreach ($ctas as $cta): ?>
					<a class="hf-hero__button hf-hero__button--<?php echo hf_e($cta['variant']); ?>" href="<?php echo hf_e($cta['url']); ?>">
						<?php echo hf_e($cta['label']); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<a class="hf-hero__scroll" href="#<?php echo hf_e($scrollTarget); ?>" aria-label="Scroll to next section">
			<span aria-hidden="true"></span>
		</a>
	</div>
</section>