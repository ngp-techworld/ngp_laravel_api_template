<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Exceptions\Auth\SessionRequired;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use Illuminate\Support\Facades\Auth;

/**
 * SPA cookie sign-in (React dashboard). The client first calls
 * GET /sanctum/csrf-cookie, then posts here with the X-XSRF-TOKEN header.
 */
final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, AuthenticateUser $authenticateUser): UserResource
    {
        if (! $request->hasSession()) {
            throw new SessionRequired;
        }

        $user = $authenticateUser->handle($request->email(), $request->password());

        Auth::guard('web')->login($user, $request->remember());
        $request->session()->regenerate();

        return UserResource::make($user);
    }
}
