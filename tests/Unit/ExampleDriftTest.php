<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\EnvFile;
use Jfrintrop\EnvValidator\EnvValidator;

it('reports no drift for a freshly generated example', function () {
    $validator = EnvValidator::make(['APP_ENV' => 'required', 'DB_PORT' => 'required|integer']);

    expect($validator->exampleDrift($validator->example()))
        ->toBe(['missing' => [], 'undeclared' => []]);
});

it('reports missing and undeclared variables', function () {
    $drift = EnvValidator::make(['APP_ENV' => 'required', 'DB_PORT' => 'required'])
        ->exampleDrift("# comment\nAPP_ENV=local\nLEGACY=1\n");

    expect($drift)->toBe(['missing' => ['DB_PORT'], 'undeclared' => ['LEGACY']]);
});

it('includes environment-only variables in the example', function () {
    $validator = EnvValidator::make(['APP_ENV' => 'required'])
        ->whenEnvironment('production', ['STRIPE_KEY' => 'required']);

    expect($validator->example())->toContain('STRIPE_KEY=');
});

it('parses env files', function () {
    expect(EnvFile::parse("# c\nexport A=1\n\nB = \"x y\"\r\nC=\n"))
        ->toBe(['A' => '1', 'B' => '"x y"', 'C' => '']);
});
