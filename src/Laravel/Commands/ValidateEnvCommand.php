<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
use Jfrintrop\EnvValidator\EnvFile;
use Jfrintrop\EnvValidator\EnvValidator;

final class ValidateEnvCommand extends Command
{
    protected $signature = 'env:validate
        {--strict : Also fail on variables in the env file that are not declared in the schema}
        {--env-file=.env : The env file to lint in --strict mode (relative to the project root, or absolute)}';

    protected $description = 'Validate environment variables against the configured schema';

    public function handle(EnvValidator $validator): int
    {
        $config = (array) $this->laravel['config']->get('env-validator', []);
        $rules = (array) ($config['rules'] ?? []);

        if ($rules === []) {
            $this->components->warn('No schema configured. Add rules in config/env-validator.php.');

            return self::SUCCESS;
        }

        if ($this->option('strict')) {
            $file = $this->resolvePath((string) $this->option('env-file'));

            if (! is_file($file)) {
                $this->components->error(sprintf('%s not found.', $file));

                return self::FAILURE;
            }

            $validator = EnvValidator::make($rules)
                ->environments((array) ($config['environments'] ?? []))
                ->messages((array) ($config['messages'] ?? []))
                ->using(EnvFile::parse((string) file_get_contents($file)))
                ->strict();
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

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->laravel->basePath($path);
    }
}
