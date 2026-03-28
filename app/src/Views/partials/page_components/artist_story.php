<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('artist_story');

$componentData = is_array($data ?? null) ? $data : [];

$sectionId = hf_normalize_section_id($componentData['section_id'] ?? 'story', 'story');
$titleId = $sectionId . '-title';
$highlightsTitleId = $sectionId . '-highlights-title';

$storyTitle = hf_data($componentData, 'story_title');
$quoteText = hf_data($componentData, 'quote_text');
$quoteAuthor = hf_data($componentData, 'quote_author');
$highlightsTitle = hf_data($componentData, 'highlights_title');

$paragraphs = [];
foreach (range(1, 3) as $index) {
	$paragraph = hf_data($componentData, 'paragraph_' . $index);
	if ($paragraph !== '') {
		$paragraphs[] = $paragraph;
	}
}

$highlights = [];
foreach (range(1, 6) as $index) {
	$title = hf_data($componentData, 'highlight_' . $index . '_title');
	$text = hf_data($componentData, 'highlight_' . $index . '_text');

	if ($title === '' && $text === '') {
		continue;
	}

	$highlights[] = [
		'title' => $title,
		'text' => $text,
	];
}
?>

<section class="as-story" id="<?php echo hf_e($sectionId); ?>" aria-labelledby="<?php echo hf_e($titleId); ?>">
	<div class="as-shell as-grid">
		<article class="as-about">
			<?php if ($storyTitle !== ''): ?>
				<h2 id="<?php echo hf_e($titleId); ?>"><?php echo hf_e($storyTitle); ?></h2>
			<?php endif; ?>

			<?php foreach ($paragraphs as $paragraph): ?>
				<p><?php echo hf_e($paragraph); ?></p>
			<?php endforeach; ?>

			<?php if ($quoteText !== '' || $quoteAuthor !== ''): ?>
				<blockquote class="as-quote">
					<?php if ($quoteText !== ''): ?>
						<p>"<?php echo hf_e($quoteText); ?>"</p>
					<?php endif; ?>
					<?php if ($quoteAuthor !== ''): ?>
						<cite>- <?php echo hf_e($quoteAuthor); ?></cite>
					<?php endif; ?>
				</blockquote>
			<?php endif; ?>
		</article>

		<aside class="as-highlights" aria-labelledby="<?php echo hf_e($highlightsTitleId); ?>">
			<?php if ($highlightsTitle !== ''): ?>
				<h3 id="<?php echo hf_e($highlightsTitleId); ?>"><?php echo hf_e($highlightsTitle); ?></h3>
			<?php endif; ?>

			<?php if ($highlights !== []): ?>
				<div class="as-highlight-list">
					<?php foreach ($highlights as $highlight): ?>
						<article class="as-highlight-item">
							<?php if ($highlight['title'] !== ''): ?>
								<h4><?php echo hf_e($highlight['title']); ?></h4>
							<?php endif; ?>
							<?php if ($highlight['text'] !== ''): ?>
								<p><?php echo hf_e($highlight['text']); ?></p>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</section>