<?php

declare(strict_types=1);

it('prunes expired bearer tokens once a day', function (): void {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('0 0 * * *  php artisan sanctum:prune-expired --hours=24')
        ->assertSuccessful();
});
