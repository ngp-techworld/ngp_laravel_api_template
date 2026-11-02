<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Customer\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Customer\Auth\RevokeTokenController;
use App\Http\Controllers\Api\V1\Customer\ItemController;
use Illuminate\Support\Facades\Route;

// auth:sanctum accepts the SPA session cookie and bearer tokens alike.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::get('me', CurrentUserController::class)->name('me');
        Route::post('logout', LogoutController::class)->name('logout');
        Route::delete('token', RevokeTokenController::class)->name('token.destroy');
    });

    Route::get('items', [ItemController::class, 'index'])->name('items.index');
});
