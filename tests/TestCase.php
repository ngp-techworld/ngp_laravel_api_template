<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Symfony's test requests default to "en-us"; send what our clients send.
        $this->withHeader('Accept-Language', 'ar');
    }
}
