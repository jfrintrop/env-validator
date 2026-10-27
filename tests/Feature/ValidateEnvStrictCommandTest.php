<?php

declare(strict_types=1);

beforeEach(function () {
    config()->set('env-validator.rules', [
        'APP_ENV' => 'required|in:local,production',
        'DB_PORT' => 'required|integer',
    ]);
});

function writeEnvFile(string $contents): string
{
    $path = sys_get_temp_dir().'/env-validator-strict-'.uniqid().'.env';
    file_put_contents($path, $contents);

    return $path;
}

it('passes in strict mode when the env file matches the schema', function () {
    $path = writeEnvFile("APP_ENV=production\nDB_PORT=5432\n");

    $this->artisan('env:validate', ['--strict' => true, '--env-file' => $path])
        ->expectsOutputToContain('Environment is valid')
        ->assertExitCode(0);

    unlink($path);
});

it('fails in strict mode on an undeclared variable in the env file', function () {
    $path = writeEnvFile("APP_ENV=production\nDB_PORT=5432\nLEGACY=1\n");

    $this->artisan('env:validate', ['--strict' => true, '--env-file' => $path])
        ->expectsOutputToContain('not declared in the schema')
        ->assertExitCode(1);

    unlink($path);
});

it('ignores comments and blank lines when parsing the env file', function () {
    $path = writeEnvFile("# a comment\n\nAPP_ENV=production\nDB_PORT=5432\n");

    $this->artisan('env:validate', ['--strict' => true, '--env-file' => $path])
        ->assertExitCode(0);

    unlink($path);
});

it('fails when the env file is missing', function () {
    $this->artisan('env:validate', ['--strict' => true, '--env-file' => '/tmp/does-not-exist-'.uniqid().'.env'])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});
