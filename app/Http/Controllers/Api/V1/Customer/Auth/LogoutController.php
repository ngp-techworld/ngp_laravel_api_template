<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer\Auth;

use App\Actions\Auth\RevokeCurrentToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Ends the current sign-in: the session for SPA clients, the token for
 * bearer clients (who normally call DELETE /api/v1/auth/token).
 */
final class LogoutController extends Controller
{
    public function __invoke(Request $request, RevokeCurrentToken $revokeCurrentToken): Response
    {
        $revokeCurrentToken->handle($request->user());

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
