# Env Validator

**English** | [Deutsch](README.de.md)

[![tests](https://github.com/jfrintrop/env-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/jfrintrop/env-validator/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/jfrintrop/env-validator.svg)](https://packagist.org/packages/jfrintrop/env-validator)
[![License](https://img.shields.io/github/license/jfrintrop/env-validator.svg)](LICENSE)

Validate your environment variables against a schema — **fail fast at boot or in CI**, using the Laravel validation rules you already know.

A missing or mistyped environment variable should blow up loudly when you deploy, not quietly three screens into a user flow. `env-validator` lets you declare exactly which variables your app needs, their types, and their allowed values — then checks them before anything else runs.

```php
EnvValidator::make([
    'APP_ENV'   => 'required|in:local,staging,production',
    'APP_DEBUG' => 'required|boolean',
    'APP_URL'   => 'required|url',
    'DB_PORT'   => 'required|integer|between:1,65535',
    'MAIL_FROM' => 'nullable|email',
])->validate();
```

If something is wrong, you get a clear, aggregated error instead of a surprise later:

```
Environment validation failed with 2 problems:
  - The APP_ENV environment variable must be one of: local, staging, production.
  - The DB_PORT environment variable must be an integer.
```

## Why

- **The syntax you already know.** It's Laravel validation rules under the hood — `required`, `in`, `boolean`, `integer`, `url`, `email`, `between`, `regex`, `nullable`, and the rest. No new DSL to learn.
- **Env-aware typing.** Environment variables arrive as strings. `APP_DEBUG=false` is correctly understood as a boolean, `null`/`empty` keywords and quoted values are handled just like Laravel's own `env()` helper.
- **Fail fast, everywhere.** Validate automatically on boot for web requests, or run `php artisan env:validate` as a one-line CI gate with a proper exit code.
- **Works standalone.** No framework required — the core builds its own validator, so you can use it in any PHP project, not just Laravel.
- **Tiny and focused.** One job, done well. No config sprawl.

**[Live demo →](https://jfrintrop.github.io/env-validator/)**

## Why not another package?

Other Laravel packages validate `.env` files too — for example
[mathiasgrimm/laravel-env-validator](https://packagist.org/packages/mathiasgrimm/laravel-env-validator)
(last release 2016), [melihovv/laravel-env-validator](https://packagist.org/packages/melihovv/laravel-env-validator)
(2020) and [teun/laravel-environment-config-validator](https://packagist.org/packages/teun/laravel-environment-config-validator).
`env-validator` is built for current Laravel (10–12) and PHP 8.1+, and adds things
you usually have to script yourself:

- **Per-environment rules** — e.g. `STRIPE_KEY` only required in `production`.
- **`.env.example` generation *and* a CI drift check** from the same schema.
- **Strict mode** to catch typos and stray variables.
- **Typed results** (`int`, `float`, `bool`) via `->cast()`.
- **Framework-free core** — usable outside Laravel.

## Installation

```bash
composer require jfrintrop/env-validator
```

Requires PHP 8.1+ and is compatible with Laravel 10, 11, and 12. The test suite runs against Laravel 12 — Laravel 10 and 11 are past their active-support window, so their latest public releases can no longer be installed from scratch in CI, but the package itself works on existing projects running them. In a Laravel app the service provider is auto-discovered — nothing else to wire up.

## Usage in Laravel

### 1. Publish the config

```bash
php artisan vendor:publish --tag=env-validator-config
```

### 2. Declare your schema

In `config/env-validator.php`:

```php
return [
    'validate_on_boot' => env('ENV_VALIDATOR_ON_BOOT', false),

    'rules' => [
        'APP_ENV'   => 'required|in:local,staging,production',
        'APP_DEBUG' => 'required|boolean',
        'APP_URL'   => 'required|url',
        'DB_PORT'   => 'required|integer|between:1,65535',
        'MAIL_FROM' => 'nullable|email',
    ],
];
```

Only the keys you list are checked; every other environment variable is left untouched.

### 3. Validate

Run it as a CI gate or a pre-deploy check:

```bash
php artisan env:validate
```

```
  INFO  Environment is valid (5 variables checked).
```

On failure it prints each problem and exits with code `1`, so your pipeline stops:

```
  ERROR  Environment validation failed:

  ⇂ The APP_ENV environment variable must be one of: local, staging, production.
  ⇂ The DB_PORT environment variable must be an integer.
```

### Validate automatically on boot

Set `validate_on_boot` to `true` (or `ENV_VALIDATOR_ON_BOOT=true`) and a misconfigured environment fails immediately on web requests, before your app does any work.

Console commands are **skipped by default** so a bad `.env` never locks you out of artisan while you fix it. Flip `validate_in_console` to `true` if you want the check to run for commands as well.

### Generate a `.env.example`

Keep your `.env.example` in sync with your schema — generate it from the same rules:

```bash
php artisan env:example
```

```ini
# Generated by env-validator — the environment variables this app expects.

# required · one of: local, staging, production
APP_ENV=local

# required · boolean (true/false)
APP_DEBUG=false

# required · URL
APP_URL=

# required · integer · between 1 and 65535
DB_PORT=

# optional · email
MAIL_FROM=
```

Each variable gets a comment describing its constraints and a sensible placeholder (the first allowed value for `in:` rules, `false` for booleans). Options:

```bash
php artisan env:example --print              # print instead of writing a file
php artisan env:example --path=.env.dist     # write somewhere else
php artisan env:example --force              # overwrite an existing file
```

### Keep `.env.example` from drifting

In CI, fail the build when `.env.example` no longer lists exactly the variables in
your schema:

```bash
php artisan env:example --check
php artisan env:example --check --path=.env.dist
```

```
  ERROR  .env.example is out of sync with the schema:

  ⇂ DB_PORT is declared in the schema but missing from the file.
  ⇂ OLD_VAR is in the file but not declared in the schema.
```

Only variable names are compared, so the placeholder values and comments you've
edited by hand are left alone. Exit code is `1` on drift.

## Usage without Laravel

The core has no framework dependency:

```php
use Jfrintrop\EnvValidator\EnvValidator;
use Jfrintrop\EnvValidator\Exceptions\EnvValidationException;

try {
    $validated = EnvValidator::make([
        'DB_PORT'  => 'required|integer|between:1,65535',
        'APP_URL'  => 'required|url',
    ])->validate();
} catch (EnvValidationException $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}
```

### Validating an explicit array

Handy for tests, or for checking a `.env` you've parsed yourself:

```php
$valid = EnvValidator::make(['DB_PORT' => 'required|integer'])
    ->using(['DB_PORT' => '5432'])
    ->passes(); // true
```

## Environment-specific rules

Some rules only make sense in one environment — production should never run with
`APP_DEBUG=true`, and a live payment key is only required there. Declare extra
rules per environment; they apply when `APP_ENV` matches and **replace** the base
rule for the same variable:

```php
// config/env-validator.php
'rules' => [
    'APP_ENV'    => 'required|in:local,staging,production',
    'APP_DEBUG'  => 'required|boolean',
    'STRIPE_KEY' => 'nullable|string',
],

'environments' => [
    'production' => [
        'APP_DEBUG'  => 'required|declined',              // must be false
        'STRIPE_KEY' => 'required|starts_with:sk_live_',
    ],
],
```

Without Laravel:

```php
EnvValidator::make([...])
    ->whenEnvironment(['staging', 'production'], ['SENTRY_DSN' => 'required|url'])
    ->environmentKey('APP_ENV') // the default
    ->validate();
```

Variables that only appear in an environment block still count as declared for
strict mode and `env:example`.

## Strict mode

Strict mode catches the opposite problem: variables that are *present but not
declared* in your schema — typos, copy-paste leftovers, config drift. It needs an
explicit source (the live environment holds unrelated system variables like
`PATH`), so pass the set you want to lint via `using()`:

```php
EnvValidator::make([
    'APP_ENV' => 'required|in:local,production',
    'DB_PORT' => 'required|integer',
])->using([
    'APP_ENV' => 'production',
    'DB_PORT' => '5432',
    'DB_PROT' => '5432',   // typo — not in the schema
])->strict()->validate();
// EnvValidationException: The DB_PROT environment variable is not declared in the schema.
```

In Laravel, lint your `.env` file against the schema straight from the CLI — ideal
as a CI check:

```bash
php artisan env:validate --strict                 # lints .env
php artisan env:validate --strict --env-file=.env.production
```

This checks both the values *and* that the file contains no variable missing from
your schema.

## Typed values

Environment variables arrive as strings. Call `->cast()` to get `validate()` to
return them with their native PHP type, inferred from the rules:

```php
$config = EnvValidator::make([
    'DB_PORT'   => 'required|integer',
    'APP_DEBUG' => 'required|boolean',
    'TIMEOUT'   => 'required|numeric',
])->cast()->validate();

$config['DB_PORT'];   // int    5432
$config['APP_DEBUG']; // bool   true
$config['TIMEOUT'];   // float  2.5
```

- `integer` / `int` → `int`
- `numeric` → `int` when the value is a whole number, otherwise `float`
- `boolean` → `bool`

Casting is off by default, so the raw validated strings are returned unless you
opt in. Nullable variables that are absent stay `null`.

## API

| Method | Description |
| --- | --- |
| `EnvValidator::make(array $rules)` | Create a validator for the given schema. |
| `->using(array $source)` | Validate against this array instead of the live environment. |
| `->cast(bool $cast = true)` | Return typed values from `validate()` (`int`, `float`, `bool`). |
| `->strict(bool $strict = true)` | Also fail on source variables not declared in the schema (needs `using()`). |
| `->whenEnvironment(string\|array $envs, array $rules)` | Apply extra rules only when the current environment matches. |
| `->environments(array $map)` | Register several environment rule sets at once (keyed by name). |
| `->environmentKey(string $key)` | Variable holding the environment name (default `APP_ENV`). |
| `->addRules(array $rules)` | Merge extra rules into the schema. |
| `->messages(array $messages)` | Override default error messages (keyed by `VAR.rule` or `rule`). |
| `->validate(): array` | Validate; returns the validated values, throws `EnvValidationException` on failure. |
| `->passes(): bool` / `->fails(): bool` | Boolean check without throwing. |
| `->errors(): array` | The error messages, keyed by variable name. |
| `->example(?string $header): string` | Render the schema as `.env.example` contents. |
| `->exampleDrift(string $contents): array` | Compare an existing `.env.example` with the schema (`missing` / `undeclared`). |

### Custom messages

```php
EnvValidator::make(['DB_PORT' => 'required|integer|between:1,65535'])
    ->messages([
        'DB_PORT.between' => 'DB_PORT must be a valid TCP port (1-65535).',
    ])
    ->validate();
```

## Testing

```bash
composer install
composer test
```

## Roadmap

Ideas under consideration:

- A production safety preset (`APP_DEBUG`, `APP_KEY`, placeholder values).
- `--format=json|github` output for CI annotations.
- Masking values of variables marked as sensitive in error output.

Have an idea or a missing rule helper? Open an issue — contributions are welcome.

## Contributing

Issues and pull requests are welcome. Please add a test for any behaviour change.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
