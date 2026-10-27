<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Tests;

use Jfrintrop\EnvValidator\Laravel\EnvValidatorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EnvValidatorServiceProvider::class,
        ];
    }
}
