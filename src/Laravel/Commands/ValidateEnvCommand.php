<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
use Jfrintrop\EnvValidator\EnvValidator;

final class ValidateEnvCommand extends Command
{
    protected $signature = 'env:validate';

    protected $description = 'Validate environment variables against the configured schema';

    public function handle(EnvValidator $validator): int
    {
        $rules = (array) $this->laravel['config']->get('env-validator.rules', []);

        if ($rules === []) {
            $this->components->warn('No schema configured. Add rules in config/env-validator.php.');

            return self::SUCCESS;
        }

        $errors = $validator->errors();

        if ($errors === []) {
            $this->components->info(sprintf(
                'Environment is valid (%d variable%s checked).',
                count($rules),
                count($rules) === 1 ? '' : 's',
            ));

            return self::SUCCESS;
        }

        $this->components->error('Environment validation failed:');
        $this->components->bulletList(array_merge(...array_values($errors)));

        return self::FAILURE;
    }
}
