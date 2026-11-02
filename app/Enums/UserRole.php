<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who a user is. Policies check it; add spatie/laravel-permission only when a
 * product needs more than "admin + user" (ngp-laravel §1).
 */
enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
