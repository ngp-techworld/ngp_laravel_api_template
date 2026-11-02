<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public\Auth;

use App\Actions\Auth\IssueAccessToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IssueTokenRequest;
use App\Http\Resources\Auth\AccessTokenResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token sign-in (mobile apps, Next.js server).
 */
final class IssueTokenController extends Controller
{
    public function __invoke(IssueTokenRequest $request, IssueAccessToken $issueAccessToken): JsonResponse
    {
        $token = $issueAccessToken->handle($request->email(), $request->password(), $request->deviceName());

        return AccessTokenResource::make($token)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
