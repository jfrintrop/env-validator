<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\EnvExampleGenerator;
use Jfrintrop\EnvValidator\EnvValidator;

it('renders a commented entry per variable', function () {
    $output = EnvExampleGenerator::fromRules([
        'APP_ENV' => 'required|in:local,staging,production',
        'APP_DEBUG' => 'required|boolean',
        'DB_PORT' => 'required|integer|between:1,65535',
        'MAIL_FROM' => 'nullable|email',
    ])->generate();

    expect($output)
        ->toContain('# required · one of: local, staging, production')
        ->toContain('APP_ENV=local')
        ->toContain('# required · boolean (true/false)')
        ->toContain('APP_DEBUG=false')
        ->toContain('# required · integer · between 1 and 65535')
        ->toContain('DB_PORT=')
        ->toContain('# optional · email')
        ->toContain('MAIL_FROM=');
});

it('marks variables without a required rule as optional', function () {
    $output = EnvExampleGenerator::fromRules(['FEATURE_FLAG' => 'boolean'])->generate();

    expect($output)->toContain('# optional · boolean (true/false)');
});

it('accepts array-style rule sets', function () {
    $output = EnvExampleGenerator::fromRules(['APP_KEY' => ['required', 'string']])->generate();

    expect($output)->toContain('# required · string')
        ->and($output)->toContain('APP_KEY=');
});

it('supports a custom header', function () {
    $output = EnvExampleGenerator::fromRules(['A' => 'required'])->generate('# My app');

    expect($output)->toStartWith('# My app');
});

it('is reachable from the EnvValidator facade-style API', function () {
    $output = EnvValidator::make(['DB_PORT' => 'required|integer'])->example();

    expect($output)->toContain('DB_PORT=');
});
