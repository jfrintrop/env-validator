<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Jfrintrop\EnvValidator\EnvValidator;
use Jfrintrop\EnvValidator\Laravel\Commands\ValidateEnvCommand;

final class EnvValidatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/env-validator.php', 'env-validator');

        $this->app->singleton(EnvValidator::class, function (Application $app): EnvValidator {
            $config = $app['config']->get('env-validator', []);

            return EnvValidator::make($config['rules'] ?? [])
                ->messages($config['messages'] ?? []);
        });

        $this->app->alias(EnvValidator::class, 'env-validator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/env-validator.php' => $this->app->configPath('env-validator.php'),
            ], 'env-validator-config');

            $this->commands([ValidateEnvCommand::class]);
        }

        $this->validateOnBoot();
    }

    private function validateOnBoot(): void
    {
        $config = $this->app['config']->get('env-validator', []);

        if (! ($config['validate_on_boot'] ?? false)) {
            return;
        }

        if ($this->app->runningInConsole() && ! ($config['validate_in_console'] ?? false)) {
            return;
        }

        if (empty($config['rules'] ?? [])) {
            return;
        }

        // Throws EnvValidationException on failure — intentional fail-fast.
        $this->app->make(EnvValidator::class)->validate();
    }
}
