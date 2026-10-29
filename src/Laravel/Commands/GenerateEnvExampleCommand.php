<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
use Jfrintrop\EnvValidator\EnvValidator;

final class GenerateEnvExampleCommand extends Command
{
    protected $signature = 'env:example
        {--path=.env.example : Where to write the file (relative to the project root, or an absolute path)}
        {--force : Overwrite the file if it already exists}
        {--print : Print to the console instead of writing a file}
        {--check : Fail if the file does not list exactly the variables in the schema (for CI)}';

    protected $description = 'Generate a .env.example file from the configured schema';

    public function handle(): int
    {
        $config = (array) $this->laravel['config']->get('env-validator', []);
        $rules = (array) ($config['rules'] ?? []);

        if ($rules === []) {
            $this->components->warn('No schema configured. Add rules in config/env-validator.php.');

            return self::SUCCESS;
        }

        $schema = EnvValidator::make($rules)->environments((array) ($config['environments'] ?? []));

        if ($this->option('check')) {
            return $this->check($schema, $this->resolvePath((string) $this->option('path')));
        }

        $contents = $schema->example();

        if ($this->option('print')) {
            $this->line($contents);

            return self::SUCCESS;
        }

        $path = $this->resolvePath((string) $this->option('path'));

        if (is_file($path) && ! $this->option('force')) {
            $this->components->error(sprintf('%s already exists. Use --force to overwrite.', $path));

            return self::FAILURE;
        }

        file_put_contents($path, $contents);

        $this->components->info(sprintf('Wrote %s (%d variable%s).', $path, count($rules), count($rules) === 1 ? '' : 's'));

        return self::SUCCESS;
    }

    private function check(EnvValidator $schema, string $path): int
    {
        if (! is_file($path)) {
            $this->components->error(sprintf('%s not found.', $path));

            return self::FAILURE;
        }

        $drift = $schema->exampleDrift((string) file_get_contents($path));

        if ($drift['missing'] === [] && $drift['undeclared'] === []) {
            $this->components->info(sprintf('%s is in sync with the schema.', basename($path)));

            return self::SUCCESS;
        }

        $this->components->error(sprintf('%s is out of sync with the schema:', basename($path)));

        $this->components->bulletList(array_merge(
            array_map(static fn (string $key): string => $key.' is declared in the schema but missing from the file.', $drift['missing']),
            array_map(static fn (string $key): string => $key.' is in the file but not declared in the schema.', $drift['undeclared']),
        ));

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
