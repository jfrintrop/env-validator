<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\Env;

it('casts boolean keywords', function () {
    $env = Env::from(['A' => 'true', 'B' => 'false', 'C' => '(true)', 'D' => '(false)']);

    expect($env->get('A'))->toBeTrue()
        ->and($env->get('B'))->toBeFalse()
        ->and($env->get('C'))->toBeTrue()
        ->and($env->get('D'))->toBeFalse();
});

it('casts null and empty keywords', function () {
    $env = Env::from(['A' => 'null', 'B' => 'empty', 'C' => '(null)', 'D' => '(empty)']);

    expect($env->get('A'))->toBeNull()
        ->and($env->get('B'))->toBe('')
        ->and($env->get('C'))->toBeNull()
        ->and($env->get('D'))->toBe('');
});

it('strips matching surrounding quotes', function () {
    $env = Env::from(['A' => '"quoted"', 'B' => "'single'", 'C' => 'plain']);

    expect($env->get('A'))->toBe('quoted')
        ->and($env->get('B'))->toBe('single')
        ->and($env->get('C'))->toBe('plain');
});

it('returns the default when a key is missing', function () {
    $env = Env::from(['A' => '1']);

    expect($env->get('MISSING', 'fallback'))->toBe('fallback')
        ->and($env->get('MISSING'))->toBeNull();
});

it('keeps numeric strings as strings', function () {
    $env = Env::from(['PORT' => '5432']);

    expect($env->get('PORT'))->toBe('5432');
});
