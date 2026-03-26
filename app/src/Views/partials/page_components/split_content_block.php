<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('split_content_block');

$heading = hf_data($data, 'heading');
$bodyText = hf_data($data, 'body_text');
$image = hf_data($data, 'image');
$imageAlignment = hf_data($data, 'image_alignment', 'right');

// Normalize alignment value to 'left' or 'right'
$imageAlignment = in_array(strtolower($imageAlignment), ['left', 'right'])
	? strtolower($imageAlignment)
	: 'right';

$imageUrl = hf_image_url($image);

// Determine the order class based on alignment
$alignmentClass = $imageAlignment === 'left' ? 'scb-reversed' : '';
?>

<section class="scb-split-content-block <?php echo $alignmentClass; ?>">
	<div class="scb-content">
		<?php if ($heading !== ''): ?>
			<h2 class="scb-heading"><?php echo hf_e($heading); ?></h2>
		<?php endif; ?>

		<?php if ($bodyText !== ''): ?>
			<p class="scb-body"><?php echo hf_e($bodyText); ?></p>
		<?php endif; ?>
	</div>

	<?php if ($imageUrl !== ''): ?>
		<figure class="scb-image-container">
			<img src="<?php echo hf_e($imageUrl); ?>" alt="<?php echo hf_e($heading); ?>" class="scb-image">
		</figure>
	<?php endif; ?>
</section>