<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Real-database concurrency tests: no RefreshDatabase transaction wrapper.
pest()->extend(TestCase::class)->in('Concurrency');
