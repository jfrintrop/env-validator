<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Validate on boot
    |--------------------------------------------------------------------------
    |
    | When true, the schema below is validated automatically during application
    | boot for web/HTTP requests, so a misconfigured environment fails fast
    | instead of surfacing deep inside a request. Console commands are skipped
    | by default (see "validate_in_console") so a bad .env never locks you out
    | of artisan while you fix it — use `php artisan env:validate` in CI.
    |
    */

    'validate_on_boot' => env('ENV_VALIDATOR_ON_BOOT', false),

    'validate_in_console' => false,

    /*
    |--------------------------------------------------------------------------
    | Schema
    |--------------------------------------------------------------------------
    |
    | Your environment variables, expressed in Laravel validation rule syntax.
    | Only the keys listed here are checked; any other environment variable is
    | left untouched.
    |
    */

    'rules' => [
        // 'APP_ENV'   => 'required|in:local,staging,production',
        // 'APP_DEBUG' => 'required|boolean',
        // 'APP_URL'   => 'required|url',
        // 'DB_PORT'   => 'required|integer|between:1,65535',
        // 'MAIL_FROM' => 'nullable|email',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom messages
    |--------------------------------------------------------------------------
    |
    | Override any default message, keyed either by "VARIABLE.rule" or "rule".
    |
    */

    'messages' => [
        // 'DB_PORT.between' => 'DB_PORT must be a valid TCP port (1-65535).',
    ],

];
