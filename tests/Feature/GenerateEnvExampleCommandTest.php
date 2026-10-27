<?php

declare(strict_types=1);

beforeEach(function () {
    config()->set('env-validator.rules', [
        'APP_ENV' => 'required|in:local,production',
        'DB_PORT' => 'required|integer',
    ]);
});

it('prints the example to the console', function () {
    $this->artisan('env:example --print')
        ->expectsOutputToContain('APP_ENV=local')
        ->assertExitCode(0);
});

it('writes the example to a file', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.example';

    $this->artisan('env:example', ['--path' => $path])
        ->assertExitCode(0);

    expect(file_get_contents($path))->toContain('APP_ENV=local');

    unlink($path);
});

it('refuses to overwrite an existing file without --force', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.example';
    file_put_contents($path, 'keep me');

    $this->artisan('env:example', ['--path' => $path])
        ->assertExitCode(1);

    expect(file_get_contents($path))->toBe('keep me');

    $this->artisan('env:example', ['--path' => $path, '--force' => true])
        ->assertExitCode(0);

    expect(file_get_contents($path))->toContain('APP_ENV=local');

    unlink($path);
});

it('warns when no schema is configured', function () {
    config()->set('env-validator.rules', []);

    $this->artisan('env:example --print')
        ->assertExitCode(0);
});
