<?php

declare(strict_types=1);

namespace Rspku\Models;

use Rspku\Helpers\ReadingTime;
use Timber\Post;

class EnhancedPost extends Post
{
    /**
     * Get reading time in minutes
     */
    public function reading_time(): int
    {
        return ReadingTime::calculate($this->post_content);
    }
    
    /**
     * Get formatted reading time
     */
    public function reading_time_text(): string
    {
        return ReadingTime::format($this->post_content);
    }

    /**
     * Return the author's credentials (e.g. "Sp.A., M.Kes.") stored in
     * user meta `_rspku_author_credentials`. Fulfils spec R2.4.
     */
    public function author_credentials(): string
    {
        $authorId = (int) $this->post_author;
        if ($authorId <= 0) {
            return '';
        }

        $value = get_user_meta($authorId, '_rspku_author_credentials', true);

        return is_string($value) ? trim($value) : '';
    }
    
    /**
     * Get last modified date (if different from published date).
     *
     * Uses {@see wp_date()} so the output honours the WordPress timezone
     * and active locale. The legacy implementation used native date()
     * against strtotime(), which meant timestamps shifted whenever the
     * server timezone didn't match WP's configured timezone and locale
     * formatting (e.g. Indonesian month names) was lost.
     */
    public function modified(string $format = ''): string
    {
        if ($format === '') {
            $format = (string) get_option('date_format', 'j M Y');
        }

        $timestamp = strtotime((string) $this->post_modified);
        if (!is_int($timestamp)) {
            return '';
        }

        $formatted = wp_date($format, $timestamp);

        return is_string($formatted) ? $formatted : '';
    }
    
    /**
     * Check if post was modified after publication
     */
    public function was_modified(): bool
    {
        return $this->post_modified !== $this->post_date;
    }
}
