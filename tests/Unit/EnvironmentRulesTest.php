<?php

declare(strict_types=1);

use Jfrintrop\EnvValidator\EnvValidator;

function schema(string $appEnv, array $extra = []): EnvValidator
{
    return EnvValidator::make([
        'APP_ENV' => 'required',
        'APP_DEBUG' => 'required|boolean',
        'STRIPE_KEY' => 'nullable|string',
    ])->whenEnvironment('production', [
        'APP_DEBUG' => 'required|declined',
        'STRIPE_KEY' => 'required|starts_with:sk_live_',
    ])->using(array_merge(['APP_ENV' => $appEnv, 'APP_DEBUG' => 'true'], $extra));
}

it('applies environment rules only when the environment matches', function () {
    expect(schema('local')->passes())->toBeTrue();

    $errors = schema('production')->errors();

    expect($errors)->toHaveKeys(['APP_DEBUG', 'STRIPE_KEY']);
});

it('passes in production once the stricter rules are satisfied', function () {
    expect(schema('production', ['APP_DEBUG' => 'false', 'STRIPE_KEY' => 'sk_live_abc'])->passes())->toBeTrue();
});

it('accepts several environment names and a custom environment key', function () {
    $validator = EnvValidator::make(['NODE_MODE' => 'required', 'TOKEN' => 'nullable'])
        ->environmentKey('NODE_MODE')
        ->whenEnvironment(['staging', 'production'], ['TOKEN' => 'required'])
        ->using(['NODE_MODE' => 'staging']);

    expect($validator->errors())->toHaveKey('TOKEN');
});

it('registers a keyed map with environments()', function () {
    $validator = EnvValidator::make(['APP_ENV' => 'required', 'TOKEN' => 'nullable'])
        ->environments(['production' => ['TOKEN' => 'required']])
        ->using(['APP_ENV' => 'production']);

    expect($validator->fails())->toBeTrue();
});

it('does not apply environment rules when the environment is unset', function () {
    $validator = EnvValidator::make(['TOKEN' => 'nullable'])
        ->whenEnvironment('production', ['TOKEN' => 'required'])
        ->using([]);

    expect($validator->passes())->toBeTrue();
});

it('treats variables declared only for an environment as declared in strict mode', function () {
    $validator = EnvValidator::make(['APP_ENV' => 'required'])
        ->whenEnvironment('production', ['STRIPE_KEY' => 'required'])
        ->using(['APP_ENV' => 'local', 'STRIPE_KEY' => 'x'])
        ->strict();

    expect($validator->passes())->toBeTrue();
});

it('casts using the effective rules of the environment', function () {
    $validated = EnvValidator::make(['APP_ENV' => 'required', 'WORKERS' => 'nullable'])
        ->whenEnvironment('production', ['WORKERS' => 'required|integer'])
        ->using(['APP_ENV' => 'production', 'WORKERS' => '4'])
        ->cast()
        ->validate();

    expect($validated['WORKERS'])->toBe(4);
});

it('has readable default messages for accepted and declined', function () {
    $errors = EnvValidator::make(['APP_DEBUG' => 'required|declined', 'FLAG' => 'required|accepted'])
        ->using(['APP_DEBUG' => 'true', 'FLAG' => 'false'])
        ->errors();

    expect($errors['APP_DEBUG'][0])->toContain('must be disabled')
        ->and($errors['FLAG'][0])->toContain('must be enabled');
});
