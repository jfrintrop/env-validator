<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\EnvValidator;
use Jfrintrop\EnvValidator\Exceptions\EnvValidationException;

it('passes when the source contains only declared variables', function () {
    $validator = EnvValidator::make([
        'APP_ENV' => 'required',
        'DB_PORT' => 'required|integer',
    ])->using([
        'APP_ENV' => 'production',
        'DB_PORT' => '5432',
    ])->strict();

    expect($validator->passes())->toBeTrue();
});

it('fails on a variable that is not declared in the schema', function () {
    $validator = EnvValidator::make(['APP_ENV' => 'required'])
        ->using([
            'APP_ENV' => 'production',
            'LEGACY_FLAG' => '1',
        ])
        ->strict();

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors())->toHaveKey('LEGACY_FLAG')
        ->and($validator->errors()['LEGACY_FLAG'][0])->toContain('not declared in the schema');
});

it('reports both value errors and undeclared variables together', function () {
    try {
        EnvValidator::make([
            'APP_ENV' => 'required|in:local,production',
        ])->using([
            'APP_ENV' => 'banana',
            'STRAY' => 'x',
        ])->strict()->validate();
    } catch (EnvValidationException $e) {
        expect($e->errors())->toHaveKeys(['APP_ENV', 'STRAY']);

        return;
    }

    $this->fail('Expected EnvValidationException was not thrown.');
});

it('throws a LogicException when used against the live environment', function () {
    EnvValidator::make(['APP_ENV' => 'required'])->strict()->errors();
})->throws(LogicException::class);

it('can be turned back off with strict(false)', function () {
    $validator = EnvValidator::make(['APP_ENV' => 'required'])
        ->using([
            'APP_ENV' => 'production',
            'EXTRA' => '1',
        ])
        ->strict()
        ->strict(false);

    expect($validator->passes())->toBeTrue();
});
