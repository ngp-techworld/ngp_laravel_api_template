<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/**
 * @property NewAccessToken $resource
 */
final class AccessTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $accessToken = $this->resource->accessToken;

        return [
            'token' => $this->resource->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessToken->expires_at?->toIso8601ZuluString(),
            'user' => UserResource::make($accessToken->tokenable),
        ];
    }
}
