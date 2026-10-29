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

it('applies configured environment rules', function () {
    config()->set('env-validator.rules', ['APP_ENV' => 'required', 'STRIPE_KEY' => 'nullable']);
    config()->set('env-validator.environments', ['production' => ['STRIPE_KEY' => 'required']]);

    $path = sys_get_temp_dir().'/env-validator-test-'.uniqid().'.env';
    file_put_contents($path, "APP_ENV=production\n");

    $this->artisan('env:validate', ['--strict' => true, '--env-file' => $path])
        ->assertExitCode(1);

    unlink($path);
});
