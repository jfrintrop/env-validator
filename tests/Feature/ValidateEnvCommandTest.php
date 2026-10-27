<?php

declare(strict_types=1);

afterEach(function () {
    unset($_ENV['ENV_TEST_PORT']);
});

it('succeeds when the environment is valid', function () {
    $_ENV['ENV_TEST_PORT'] = '5432';
    config()->set('env-validator.rules', ['ENV_TEST_PORT' => 'required|integer']);

    $this->artisan('env:validate')
        ->expectsOutputToContain('Environment is valid')
        ->assertExitCode(0);
});

it('fails with a non-zero exit code when the environment is invalid', function () {
    $_ENV['ENV_TEST_PORT'] = 'not-a-number';
    config()->set('env-validator.rules', ['ENV_TEST_PORT' => 'required|integer']);

    $this->artisan('env:validate')
        ->expectsOutputToContain('Environment validation failed')
        ->assertExitCode(1);
});

it('warns and succeeds when no schema is configured', function () {
    config()->set('env-validator.rules', []);

    $this->artisan('env:validate')
        ->assertExitCode(0);
});
