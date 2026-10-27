<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\EnvValidator;
use Jfrintrop\EnvValidator\Exceptions\EnvValidationException;

it('passes for a valid environment', function () {
    $result = EnvValidator::make([
        'APP_ENV' => 'required|in:local,staging,production',
        'APP_DEBUG' => 'required|boolean',
        'DB_PORT' => 'required|integer|between:1,65535',
    ])->using([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'DB_PORT' => '5432',
    ])->validate();

    expect($result)->toMatchArray([
        'APP_ENV' => 'production',
        'APP_DEBUG' => false,
        'DB_PORT' => '5432',
    ]);
});

it('fails when a required variable is missing', function () {
    $validator = EnvValidator::make(['APP_KEY' => 'required'])->using([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors())->toHaveKey('APP_KEY');
});

it('throws with a readable, aggregated message', function () {
    EnvValidator::make([
        'APP_ENV' => 'required|in:local,production',
        'DB_PORT' => 'required|integer',
    ])->using([
        'APP_ENV' => 'banana',
        'DB_PORT' => 'not-a-number',
    ])->validate();
})->throws(EnvValidationException::class);

it('reports the variable name verbatim in messages', function () {
    $errors = EnvValidator::make(['DB_PORT' => 'required|integer'])
        ->using(['DB_PORT' => 'nope'])
        ->errors();

    expect($errors['DB_PORT'][0])->toContain('DB_PORT');
});

it('collects every problem, not just the first', function () {
    try {
        EnvValidator::make([
            'A' => 'required',
            'B' => 'required',
        ])->using([])->validate();
    } catch (EnvValidationException $e) {
        expect($e->messages())->toHaveCount(2)
            ->and($e->errors())->toHaveKeys(['A', 'B']);

        return;
    }

    $this->fail('Expected EnvValidationException was not thrown.');
});

it('treats nullable variables as optional', function () {
    $validator = EnvValidator::make(['MAIL_FROM' => 'nullable|email'])->using([]);

    expect($validator->passes())->toBeTrue();
});

it('lets callers override messages', function () {
    $errors = EnvValidator::make(['DB_PORT' => 'required'])
        ->messages(['DB_PORT.required' => 'Set the database port!'])
        ->using([])
        ->errors();

    expect($errors['DB_PORT'][0])->toBe('Set the database port!');
});
