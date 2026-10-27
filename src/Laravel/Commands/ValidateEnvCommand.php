<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
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
                ->messages((array) ($config['messages'] ?? []))
                ->using($this->parseEnvFile($file))
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

    /**
     * @return array<string, string>
     */
    private function parseEnvFile(string $path): array
    {
        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if (str_starts_with($key, 'export ')) {
                $key = trim(substr($key, 7));
            }

            $values[$key] = trim($value);
        }

        return $values;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->laravel->basePath($path);
    }
}
