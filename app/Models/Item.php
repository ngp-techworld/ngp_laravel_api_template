<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUppercaseUlids;
use App\Support\LikePattern;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The example feature: something a user owns. Replace it with your first
 * real domain model.
 */
#[Fillable(['name', 'description'])]
final class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, HasUppercaseUlids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Case-insensitive "name contains" (ILIKE on PostgreSQL).
     *
     * @param  Builder<self>  $query
     */
    public function scopeNameContains(Builder $query, string $term): void
    {
        $query->whereLike('name', LikePattern::contains($term));
    }
}
