<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Public\Auth\IssueTokenController;
use App\Http\Controllers\Api\V1\Public\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Both sign-in modes share the "login" limiter: 5 per minute per email + IP.
Route::prefix('auth')->name('auth.')->middleware('throttle:login')->group(function (): void {
    Route::post('login', LoginController::class)->name('login');
    Route::post('token', IssueTokenController::class)->name('token.store');
});
