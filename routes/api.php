<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Every endpoint lives under /api/v1 (ngp-laravel §5). One file per audience:
| public.php (no sign-in), customer.php (signed-in users). Add admin.php the
| same way when a product needs it. A breaking change goes to /api/v2.
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    require __DIR__.'/api/v1/public.php';
    require __DIR__.'/api/v1/customer.php';
});
