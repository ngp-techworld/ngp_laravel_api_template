<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
|
| Feature tests boot the app and run on a fresh PostgreSQL "app_test"
| database (migrated once, each test wrapped in a transaction).
| Unit tests boot the app too (translator, config) but never touch the DB.
|
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
