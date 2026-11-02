<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds LIKE/ILIKE patterns from user input.
 */
final class LikePattern
{
    /**
     * Pattern that matches $term anywhere in a column. "%", "_" and "\" in
     * the term are escaped, so a search for "50%" means the text "50%".
     */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '\%_').'%';
    }
}
