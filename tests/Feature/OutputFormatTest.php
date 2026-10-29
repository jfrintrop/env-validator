<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config()->set('env-validator.rules', ['ENV_TEST_PORT' => 'required|integer']);
    $_ENV['ENV_TEST_PORT'] = 'nope';
});

afterEach(function () {
    unset($_ENV['ENV_TEST_PORT']);
});

function runCommand(string $command, array $options): array
{
    $exit = Artisan::call($command, $options);

    return [$exit, Artisan::output()];
}

it('prints a json report for env:validate', function () {
    [$exit, $output] = runCommand('env:validate', ['--format' => 'json']);
    $report = json_decode($output, true);

    expect($exit)->toBe(1)
        ->and($report['valid'])->toBeFalse()
        ->and($report['checked'])->toBe(1)
        ->and($report['errors']['ENV_TEST_PORT'][0])->toContain('must be an integer');
});

it('prints a valid json report with an empty errors object', function () {
    $_ENV['ENV_TEST_PORT'] = '1';

    [$exit, $output] = runCommand('env:validate', ['--format' => 'json']);

    expect($exit)->toBe(0)
        ->and($output)->toContain('"valid": true')
        ->and($output)->toContain('"errors": {}');
});

it('prints github annotations for env:validate and nothing when valid', function () {
    [$exit, $output] = runCommand('env:validate', ['--format' => 'github']);

    expect($exit)->toBe(1)
        ->and($output)->toContain('::error title=Environment validation::The ENV_TEST_PORT');

    $_ENV['ENV_TEST_PORT'] = '1';
    [$exit, $output] = runCommand('env:validate', ['--format' => 'github']);

    expect($exit)->toBe(0)->and(trim($output))->toBe('');
});

it('points github annotations at the linted file in strict mode', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.env';
    file_put_contents($path, "ENV_TEST_PORT=nope\n");

    [, $output] = runCommand('env:validate', ['--strict' => true, '--env-file' => $path, '--format' => 'github']);

    expect($output)->toContain('::error file='.str_replace([':', ','], ['%3A', '%2C'], $path));

    unlink($path);
});

it('rejects unknown formats with exit code 2', function () {
    [$exit] = runCommand('env:validate', ['--format' => 'xml']);

    expect($exit)->toBe(2);
});

it('prints a json drift report for env:example --check', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.example';
    file_put_contents($path, "OLD=1\n");

    [$exit, $output] = runCommand('env:example', ['--check' => true, '--path' => $path, '--format' => 'json']);
    $report = json_decode($output, true);

    expect($exit)->toBe(1)
        ->and($report)->toBe(['valid' => false, 'missing' => ['ENV_TEST_PORT'], 'undeclared' => ['OLD']]);

    unlink($path);
});

it('prints github annotations for env:example --check', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.example';
    file_put_contents($path, "OLD=1\n");

    [, $output] = runCommand('env:example', ['--check' => true, '--path' => $path, '--format' => 'github']);

    expect($output)->toContain('title=Env example drift::ENV_TEST_PORT is declared')
        ->and($output)->toContain('::OLD is in the file');

    unlink($path);
});

it('reports a missing file as json', function () {
    [$exit, $output] = runCommand('env:example', ['--check' => true, '--path' => '/nonexistent/x', '--format' => 'json']);

    expect($exit)->toBe(1)->and(json_decode($output, true)['valid'])->toBeFalse();
});
