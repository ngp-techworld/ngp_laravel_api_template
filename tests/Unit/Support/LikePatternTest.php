<?php

declare(strict_types=1);

use App\Support\LikePattern;

it('matches the term anywhere', function (): void {
    expect(LikePattern::contains('milk'))->toBe('%milk%');
});

it('treats LIKE wildcards in the term as plain text', function (string $term, string $pattern): void {
    expect(LikePattern::contains($term))->toBe($pattern);
})->with([
    'percent' => ['50%', '%50\%%'],
    'underscore' => ['snake_case', '%snake\_case%'],
    'backslash' => ['a\b', '%a\\\\b%'],
]);

it('keeps Arabic text as it is', function (): void {
    expect(LikePattern::contains('حليب'))->toBe('%حليب%');
});
