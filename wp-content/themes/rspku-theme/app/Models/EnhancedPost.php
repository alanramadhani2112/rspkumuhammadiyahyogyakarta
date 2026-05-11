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
     * Get last modified date (if different from published date)
     */
    public function modified(string $format = ''): string
    {
        if (empty($format)) {
            $format = get_option('date_format');
        }
        
        return date($format, strtotime($this->post_modified));
    }
    
    /**
     * Check if post was modified after publication
     */
    public function was_modified(): bool
    {
        return $this->post_modified !== $this->post_date;
    }
}
