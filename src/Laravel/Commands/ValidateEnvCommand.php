<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Commands;

use Illuminate\Console\Command;
use Jfrintrop\EnvValidator\EnvFile;
use Jfrintrop\EnvValidator\EnvValidator;
use Jfrintrop\EnvValidator\Report;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidateEnvCommand extends Command
{
    protected $signature = 'env:validate
        {--strict : Also fail on variables in the env file that are not declared in the schema}
        {--env-file=.env : The env file to lint in --strict mode (relative to the project root, or absolute)}
        {--format=text : Output format: text, json or github (GitHub Actions annotations)}';

    protected $description = 'Validate environment variables against the configured schema';

    public function handle(EnvValidator $validator): int
    {
        $format = (string) $this->option('format');

        if (! Report::isSupported($format)) {
            $this->components->error(sprintf('Unknown format "%s". Use text, json or github.', $format));

            return self::INVALID;
        }

        $config = (array) $this->laravel['config']->get('env-validator', []);
        $rules = (array) ($config['rules'] ?? []);

        if ($rules === []) {
            if ($format === Report::JSON) {
                $this->raw(Report::json(true, ['checked' => 0, 'errors' => (object) []]));
            } elseif ($format === Report::TEXT) {
                $this->components->warn('No schema configured. Add rules in config/env-validator.php.');
            }

            return self::SUCCESS;
        }

        if ($this->option('strict')) {
            $file = $this->resolvePath((string) $this->option('env-file'));

            if (! is_file($file)) {
                return $this->failWith($format, sprintf('%s not found.', $file));
            }

            $validator = EnvValidator::make($rules)
                ->environments((array) ($config['environments'] ?? []))
                ->messages((array) ($config['messages'] ?? []))
                ->using(EnvFile::parse((string) file_get_contents($file)))
                ->strict();
        }

        $errors = $validator->errors();

        if ($format === Report::JSON) {
            $this->raw(Report::json($errors === [], ['checked' => count($rules), 'errors' => (object) $errors]));

            return $errors === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($format === Report::GITHUB) {
            if ($errors !== []) {
                $file = $this->option('strict') ? (string) $this->option('env-file') : null;
                $this->raw(Report::github(array_merge(...array_values($errors)), $file, 'Environment validation'));
            }

            return $errors === [] ? self::SUCCESS : self::FAILURE;
        }

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

    private function failWith(string $format, string $message): int
    {
        match ($format) {
            Report::JSON => $this->raw(Report::json(false, ['error' => $message])),
            Report::GITHUB => $this->raw(Report::github([$message], null, 'Environment validation')),
            default => $this->components->error($message),
        };

        return self::FAILURE;
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
