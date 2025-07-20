<?php

declare(strict_types=1);

use Orchestra\Testbench\TestCase;
use PayazaSdk\PayazaServiceProvider;

uses(TestCase::class)->in('Feature', 'Unit');

function getPackageProviders($app): array
{
    return [PayazaServiceProvider::class];
}