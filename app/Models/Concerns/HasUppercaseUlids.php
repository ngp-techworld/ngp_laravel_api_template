<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Str;

/**
 * ULID primary keys in their canonical upper-case form ("01JC..."), as the
 * API contract shows them. Laravel's HasUlids lower-cases them.
 */
trait HasUppercaseUlids
{
    use HasUlids;

    public function newUniqueId(): string
    {
        return (string) Str::ulid();
    }
}
