<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer\Auth;

use App\Actions\Auth\RevokeCurrentToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class RevokeTokenController extends Controller
{
    public function __invoke(Request $request, RevokeCurrentToken $revokeCurrentToken): Response
    {
        $revokeCurrentToken->handle($request->user());

        return response()->noContent();
    }
}
