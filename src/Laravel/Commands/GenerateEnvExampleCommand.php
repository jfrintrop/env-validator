<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
use Jfrintrop\EnvValidator\EnvValidator;
use Jfrintrop\EnvValidator\Report;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateEnvExampleCommand extends Command
{
    protected $signature = 'env:example
        {--path=.env.example : Where to write the file (relative to the project root, or an absolute path)}
        {--force : Overwrite the file if it already exists}
        {--print : Print to the console instead of writing a file}
        {--check : Fail if the file does not list exactly the variables in the schema (for CI)}
        {--format=text : Output format for --check: text, json or github (GitHub Actions annotations)}';

    protected $description = 'Generate a .env.example file from the configured schema';

    public function handle(): int
    {
        $format = (string) $this->option('format');

        if (! Report::isSupported($format)) {
            $this->components->error(sprintf('Unknown format "%s". Use text, json or github.', $format));

            return self::INVALID;
        }

        $config = (array) $this->laravel['config']->get('env-validator', []);
        $rules = (array) ($config['rules'] ?? []);

        if ($rules === []) {
            $this->components->warn('No schema configured. Add rules in config/env-validator.php.');

            return self::SUCCESS;
        }

        $schema = EnvValidator::make($rules)->environments((array) ($config['environments'] ?? []));

        if ($this->option('check')) {
            return $this->check($schema, (string) $this->option('path'), $format);
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

    private function check(EnvValidator $schema, string $option, string $format): int
    {
        $path = $this->resolvePath($option);

        if (! is_file($path)) {
            $message = sprintf('%s not found.', $path);

            match ($format) {
                Report::JSON => $this->raw(Report::json(false, ['error' => $message])),
                Report::GITHUB => $this->raw(Report::github([$message], null, 'Env example drift')),
                default => $this->components->error($message),
            };

            return self::FAILURE;
        }

        $drift = $schema->exampleDrift((string) file_get_contents($path));
        $inSync = $drift['missing'] === [] && $drift['undeclared'] === [];

        $messages = array_merge(
            array_map(static fn (string $key): string => $key.' is declared in the schema but missing from the file.', $drift['missing']),
            array_map(static fn (string $key): string => $key.' is in the file but not declared in the schema.', $drift['undeclared']),
        );

        if ($format === Report::JSON) {
            $this->raw(Report::json($inSync, ['missing' => $drift['missing'], 'undeclared' => $drift['undeclared']]));
        } elseif ($format === Report::GITHUB) {
            if (! $inSync) {
                $this->raw(Report::github($messages, $option, 'Env example drift'));
            }
        } elseif ($inSync) {
            $this->components->info(sprintf('%s is in sync with the schema.', basename($path)));
        } else {
            $this->components->error(sprintf('%s is out of sync with the schema:', basename($path)));
            $this->components->bulletList($messages);
        }

        return $inSync ? self::SUCCESS : self::FAILURE;
    }

    private function raw(string $output): void
    {
        $this->output->writeln($output, OutputInterface::OUTPUT_RAW);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->laravel->basePath($path);
    }
}
