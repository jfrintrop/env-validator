<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
use Jfrintrop\EnvValidator\EnvExampleGenerator;

final class GenerateEnvExampleCommand extends Command
{
    protected $signature = 'env:example
        {--path=.env.example : Where to write the file (relative to the project root, or an absolute path)}
        {--force : Overwrite the file if it already exists}
        {--print : Print to the console instead of writing a file}';

    protected $description = 'Generate a .env.example file from the configured schema';

    public function handle(): int
    {
        $rules = (array) $this->laravel['config']->get('env-validator.rules', []);

        if ($rules === []) {
            $this->components->warn('No schema configured. Add rules in config/env-validator.php.');

            return self::SUCCESS;
        }

        $contents = EnvExampleGenerator::fromRules($rules)->generate();

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

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->laravel->basePath($path);
    }
}
