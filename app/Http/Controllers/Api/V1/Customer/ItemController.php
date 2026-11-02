<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Item\ListItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ListItemsRequest;
use App\Http\Resources\Item\ItemResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ItemController extends Controller
{
    public function index(ListItemsRequest $request, ListItems $listItems): AnonymousResourceCollection
    {
        $items = $listItems->handle($request->user(), $request->perPage(), $request->page(), $request->search());

        // withQueryString() keeps per_page and search in the pagination links.
        return ItemResource::collection($items->withQueryString());
    }
}
