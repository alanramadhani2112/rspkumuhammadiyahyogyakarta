<?php

declare(strict_types=1);

namespace Rspku\Helpers;

final class ReadingTime
{
    /**
     * Calculate reading time in minutes
     * Average reading speed: 200 words per minute
     */
    public static function calculate(string $content): int
    {
        // Strip HTML tags and shortcodes
        $text = wp_strip_all_tags(strip_shortcodes($content));
        
        // Count words
        $wordCount = str_word_count($text);
        
        // Calculate reading time (200 words per minute)
        $minutes = (int) ceil($wordCount / 200);
        
        // Minimum 1 minute
        return max(1, $minutes);
    }
    
    /**
     * Get formatted reading time string
     */
    public static function format(string $content): string
    {
        $minutes = self::calculate($content);
        
        return $minutes . ' min baca';
    }
}
