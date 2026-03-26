<?php

if (!function_exists('hf_e')) {
	function hf_e($value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('hf_image_url')) {
	function hf_image_url($filename): string
	{
		$name = trim((string) $filename);
		return $name !== '' ? '/images/' . rawurlencode($name) : '';
	}
}

if (!function_exists('hf_data')) {
	function hf_data(array $data, string $key, string $default = ''): string
	{
		return trim((string) ($data[$key] ?? $default));
	}
}

if (!function_exists('hf_normalize_section_id')) {
	function hf_normalize_section_id($raw, string $fallback): string
	{
		$id = trim((string) $raw);
		$id = preg_replace('/[^a-z0-9_-]+/i', '-', $id) ?? $fallback;
		$id = trim($id, '-_');

		return $id !== '' ? $id : $fallback;
	}
}

/**
 * Get all defined component assets (fonts, preconnect tags, and stylesheets)
 */
if (!function_exists('hf_get_component_assets')) {
	function hf_get_component_assets(): array
	{
		return [
			'venues_map' => [
				'stylesheets' => ['/css/venues_map.css'],
				'fonts' => 'barlow_serif',
			],
			'lineup_section' => [
				'stylesheets' => ['/css/lineup_section.css'],
				'fonts' => 'barlow_serif_inter',
			],
			'tickets_passes' => [
				'stylesheets' => ['/css/primitives.css', '/css/tickets_passes.css'],
				'fonts' => 'barlow_serif',
			],
			'hero_banner' => [
				'stylesheets' => ['/css/hero_banner.css'],
				'fonts' => null,
			],
			'split_content_block' => [
				'stylesheets' => ['/css/primitives.css', '/css/split_content_block.css'],
				'fonts' => null,
			],
		];
	}
}

/**
 * Get font definitions by key
 */
if (!function_exists('hf_get_font_definitions')) {
	function hf_get_font_definitions(): array
	{
		return [
			'barlow_serif' => [
				'<link rel="preconnect" href="https://fonts.googleapis.com">',
				'<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
				'<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700;800&family=Barlow+Semi+Condensed:wght@300;400;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">',
			],
			'barlow_serif_inter' => [
				'<link rel="preconnect" href="https://fonts.googleapis.com">',
				'<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
				'<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700;800&family=Barlow+Semi+Condensed:wght@300;400;600&family=Instrument+Serif:ital@0;1&family=Inter:wght@400;600&display=swap" rel="stylesheet">',
			],
		];
	}
}

/**
 * Register a component to have its assets loaded
 */
if (!function_exists('hf_register_component')) {
	function hf_register_component(string $componentName): void
	{
		if (!isset($_SESSION['_hf_registered_components'])) {
			$_SESSION['_hf_registered_components'] = [];
		}
		$_SESSION['_hf_registered_components'][$componentName] = true;
	}
}

/**
 * Get all registered component assets for rendering in the head
 */
if (!function_exists('hf_render_component_assets')) {
	function hf_render_component_assets(array $registeredComponents = []): string
	{
		$output = '';
		$componentAssets = hf_get_component_assets();
		$fontDefinitions = hf_get_font_definitions();
		$emittedTags = [];
		$loadedStylesheets = [];

		foreach ($registeredComponents as $componentName => $_) {
			if (!isset($componentAssets[$componentName])) {
				continue;
			}

			$component = $componentAssets[$componentName];

			// Load fonts (avoid duplicates at the tag level)
			$fontKey = $component['fonts'] ?? null;
			if ($fontKey && isset($fontDefinitions[$fontKey])) {
				foreach ($fontDefinitions[$fontKey] as $fontTag) {
					if (!isset($emittedTags[$fontTag])) {
						$output .= $fontTag . "\n";
						$emittedTags[$fontTag] = true;
					}
				}
			}

			// Load stylesheets (avoid duplicates)
			foreach ($component['stylesheets'] as $stylesheet) {
				if (!isset($loadedStylesheets[$stylesheet])) {
					$output .= '<link rel="stylesheet" href="' . hf_e($stylesheet) . '">' . "\n";
					$loadedStylesheets[$stylesheet] = true;
				}
			}
		}

		return $output;
	}
}
