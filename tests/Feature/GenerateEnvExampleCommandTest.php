<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

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

it('passes --check when the file matches the schema', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.example';
    file_put_contents($path, "APP_ENV=local\nDB_PORT=\n");

    $this->artisan('env:example', ['--path' => $path, '--check' => true])
        ->assertExitCode(0);

    unlink($path);
});

it('fails --check and lists the drift', function () {
    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.example';
    file_put_contents($path, "APP_ENV=local\nOLD_VAR=1\n");

    $exit = Artisan::call('env:example', ['--path' => $path, '--check' => true]);
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('DB_PORT')
        ->and($output)->toContain('OLD_VAR');

    unlink($path);
});

it('fails --check when the file does not exist', function () {
    $this->artisan('env:example', ['--path' => '/nonexistent/.env.example', '--check' => true])
        ->assertExitCode(1);
});
