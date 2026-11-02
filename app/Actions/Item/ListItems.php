<?php

declare(strict_types=1);

namespace App\Actions\Item;

use App\Models\Item;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListItems
{
    /**
     * The user's own items, newest first, optionally filtered by name.
     *
     * @return LengthAwarePaginator<int, Item>
     */
    public function handle(User $user, int $perPage, int $page = 1, ?string $search = null): LengthAwarePaginator
    {
        $query = $user->items()
            ->orderByDesc('created_at')
            ->orderByDesc('id'); // ULIDs sort by time: a stable order for equal timestamps

        if ($search !== null) {
            $query->nameContains($search);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
