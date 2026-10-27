<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\EnvValidator;

it('returns raw strings by default (no casting)', function () {
    $result = EnvValidator::make([
        'DB_PORT' => 'required|integer',
        'TIMEOUT' => 'required|numeric',
    ])->using([
        'DB_PORT' => '5432',
        'TIMEOUT' => '2.5',
    ])->validate();

    expect($result['DB_PORT'])->toBe('5432')
        ->and($result['TIMEOUT'])->toBe('2.5');
});

it('casts integer rules to int', function () {
    $result = EnvValidator::make(['DB_PORT' => 'required|integer'])
        ->using(['DB_PORT' => '5432'])
        ->cast()
        ->validate();

    expect($result['DB_PORT'])->toBe(5432);
});

it('casts boolean rules to bool', function () {
    $result = EnvValidator::make([
        'APP_DEBUG' => 'required|boolean',
        'FLAG_ONE' => 'required|boolean',
        'FLAG_ZERO' => 'required|boolean',
    ])->using([
        'APP_DEBUG' => 'true',
        'FLAG_ONE' => '1',
        'FLAG_ZERO' => '0',
    ])->cast()->validate();

    expect($result['APP_DEBUG'])->toBeTrue()
        ->and($result['FLAG_ONE'])->toBeTrue()
        ->and($result['FLAG_ZERO'])->toBeFalse();
});

it('casts numeric rules to int or float as appropriate', function () {
    $result = EnvValidator::make([
        'WHOLE' => 'required|numeric',
        'FRACTION' => 'required|numeric',
    ])->using([
        'WHOLE' => '10',
        'FRACTION' => '2.5',
    ])->cast()->validate();

    expect($result['WHOLE'])->toBe(10)
        ->and($result['FRACTION'])->toBe(2.5);
});

it('leaves nullable missing values as null when casting', function () {
    $result = EnvValidator::make(['MAYBE_PORT' => 'nullable|integer'])
        ->using([])
        ->cast()
        ->validate();

    expect($result)->toHaveKey('MAYBE_PORT')
        ->and($result['MAYBE_PORT'])->toBeNull();
});

it('can be turned back off with cast(false)', function () {
    $result = EnvValidator::make(['DB_PORT' => 'required|integer'])
        ->using(['DB_PORT' => '5432'])
        ->cast()
        ->cast(false)
        ->validate();

    expect($result['DB_PORT'])->toBe('5432');
});
