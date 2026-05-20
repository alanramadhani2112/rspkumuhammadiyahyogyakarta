<?php

declare(strict_types=1);

namespace Rspku\Helpers;

final class ReadingTime
{
    /**
     * Average reading speed in words per minute. Exposed as a constant
     * so tests and callers can reason about the calibration without
     * scraping the implementation.
     */
    public const WORDS_PER_MINUTE = 200;

    /**
     * Calculate reading time in minutes.
     *
     * The previous implementation relied on {@see str_word_count()},
     * which is ASCII-only and silently undercounts Indonesian text with
     * diacritics (e.g. "Açhmad"), inline numbers (dose strengths), and
     * any other non-Latin-1 glyphs. We now count whitespace-separated
     * tokens with a Unicode-aware regex so bahasa Indonesia, medical
     * terminology, and embedded numerics are all counted consistently.
     */
    public static function calculate(string $content): int
    {
        $text = wp_strip_all_tags(strip_shortcodes($content));
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            return 1;
        }

        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = is_array($tokens) ? count($tokens) : 0;

        if ($wordCount <= 0) {
            return 1;
        }

        $minutes = (int) ceil($wordCount / self::WORDS_PER_MINUTE);

        return max(1, $minutes);
    }

    /**
     * Get formatted reading time string
     */
    public static function format(string $content): string
    {
        $minutes = self::calculate($content);

        return sprintf(
            /* translators: %d: reading time in minutes */
            _n('%d menit baca', '%d menit baca', $minutes, 'rspku-theme'),
            $minutes
        );
    }
}
