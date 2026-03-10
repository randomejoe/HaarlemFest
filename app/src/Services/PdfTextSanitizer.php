<?php

namespace App\Services;

final class PdfTextSanitizer
{
    public static function normalize(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);

        if (class_exists(\Normalizer::class)) {
            $candidate = \Normalizer::normalize($normalized, \Normalizer::FORM_KD);
            if (is_string($candidate)) {
                $normalized = $candidate;
            }
        }

        if (function_exists('iconv')) {
            $candidate = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if ($candidate !== false) {
                $normalized = $candidate;
            }
        }

        $normalized = preg_replace('/[^\x20-\x7E]/', '?', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    public static function wrap(string $text, int $maxChars): array
    {
        $text = self::normalize($text);
        if ($text === '') {
            return [''];
        }

        if ($maxChars <= 0) {
            return [$text];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            while (strlen($word) > $maxChars) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }

                $lines[] = substr($word, 0, $maxChars);
                $word = substr($word, $maxChars);
            }

            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : ($current . ' ' . $word);
            if (strlen($candidate) <= $maxChars) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [''] : $lines;
    }
}
