# Env Validator

**English** | [Deutsch](README.de.md)

[![tests](https://github.com/jfrintrop/env-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/jfrintrop/env-validator/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/jfrintrop/env-validator.svg)](https://packagist.org/packages/jfrintrop/env-validator)
[![License](https://img.shields.io/github/license/jfrintrop/env-validator.svg)](LICENSE)

Validate your environment variables against a schema — **fail fast at boot or in CI**, using the Laravel validation rules you already know.

A missing or mistyped environment variable should blow up loudly when you deploy, not quietly three screens into a user flow.

**[Live demo →](https://jfrintrop.github.io/env-validator/)**

## What it can do

- **Validate against a schema** — declare required variables, types and allowed values with plain Laravel validation rules. → [Quick start](#quick-start)
- **Env-aware typing** — `"false"` is a boolean, `null`/`empty` keywords and quoted values behave like Laravel's `env()`. → [Quick start](#quick-start)
- **Fail fast on boot** — optional automatic check for web requests. → [Validate on boot](#validate-on-boot)
- **CI gate** — `php artisan env:validate` with proper exit codes. → [Commands](#commands)
- **Per-environment rules** — e.g. `STRIPE_KEY` required only in `production`, `APP_DEBUG` must be `false` there. → [Environment-specific rules](#environment-specific-rules)
- **Strict mode** — catch typos and stray variables (`DB_PROT`) that aren't in the schema. → [Strict mode](#strict-mode)
- **`.env.example` generation** — written from the same schema, with constraint comments. → [The .env.example file](#the-envexample-file)
- **Drift check** — fail CI when `.env.example` and the schema diverge. → [The .env.example file](#the-envexample-file)
- **CI-friendly output** — plain text, JSON, or GitHub Actions annotations on pull requests. → [CI output formats](#ci-output-formats)
- **Typed results** — opt in to `int`, `float` and `bool` values. → [Typed values](#typed-values)
- **Custom messages** — override any error text. → [Custom messages](#custom-messages)
- **Works standalone** — the core needs no framework, any PHP 8.1+ project. → [Usage without Laravel](#usage-without-laravel)

## Quick start

```bash
composer require jfrintrop/env-validator
php artisan vendor:publish --tag=env-validator-config
```

Declare your schema in `config/env-validator.php`:

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

Only the keys you list are checked; every other environment variable is left untouched. Then run:

```bash
php artisan env:validate
```

```
  INFO  Environment is valid (5 variables checked).
```

On failure you get every problem at once and exit code `1`, so your pipeline stops:

```
  ERROR  Environment validation failed:

  ⇂ The APP_ENV environment variable must be one of: local, staging, production.
  ⇂ The DB_PORT environment variable must be an integer.
```

Requires PHP 8.1+ and is compatible with Laravel 10, 11 and 12. The service provider is auto-discovered. The test suite runs against Laravel 12 — Laravel 10 and 11 are past their active-support window, so their latest public releases can no longer be installed from scratch in CI, but the package itself works on existing projects running them.

## Commands

| Command | What it does |
| --- | --- |
| `php artisan env:validate` | Validate the environment against the schema. |
| `… --strict` | Also fail on variables in the env file that aren't in the schema. |
| `… --env-file=.env.production` | Which file to lint in strict mode (default `.env`). |
| `… --format=text\|json\|github` | Output format (default `text`). |
| `php artisan env:example` | Generate `.env.example` from the schema. |
| `… --print` | Print instead of writing a file. |
| `… --path=.env.dist` | Write somewhere else. |
| `… --force` | Overwrite an existing file. |
| `… --check` | Fail if the file doesn't list exactly the schema's variables. |
| `… --format=text\|json\|github` | Output format for `--check`. |

Exit codes: `0` success, `1` validation failure or drift, `2` unknown `--format`.

## Validate on boot

Set `validate_on_boot` to `true` (or `ENV_VALIDATOR_ON_BOOT=true`) and a misconfigured environment fails immediately on web requests, before your app does any work.

Console commands are **skipped by default** so a bad `.env` never locks you out of artisan while you fix it. Set `validate_in_console` to `true` if you want the check to run for commands as well.

## Environment-specific rules

Some rules only make sense in one environment — production should never run with `APP_DEBUG=true`, and a live payment key is only required there. Declare extra rules per environment; they apply when `APP_ENV` matches and **replace** the base rule for the same variable:

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

Variables that only appear in an environment block still count as declared for strict mode and `env:example`.

## Strict mode

Strict mode catches the opposite problem: variables that are *present but not declared* in your schema — typos, copy-paste leftovers, config drift. It needs an explicit source (the live environment holds unrelated system variables like `PATH`), so pass the set you want to lint via `using()`:

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

In Laravel, lint your `.env` file straight from the CLI — it checks both the values *and* that the file contains no variable missing from your schema:

```bash
php artisan env:validate --strict                 # lints .env
php artisan env:validate --strict --env-file=.env.production
```

## The .env.example file

### Generate it

Keep your `.env.example` in sync with your schema by generating it from the same rules:

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

Each variable gets a comment describing its constraints and a sensible placeholder (the first allowed value for `in:` rules, `false` for booleans).

### Check for drift

In CI, fail the build when `.env.example` no longer lists exactly the variables in your schema:

```bash
php artisan env:example --check
php artisan env:example --check --path=.env.dist
```

```
  ERROR  .env.example is out of sync with the schema:

  ⇂ DB_PORT is declared in the schema but missing from the file.
  ⇂ OLD_VAR is in the file but not declared in the schema.
```

Only variable names are compared, so the placeholder values and comments you've edited by hand are left alone.

## CI output formats

Both `env:validate` and `env:example --check` accept `--format`:

- `text` (default) — human-readable output.
- `json` — machine-readable, for scripts and dashboards:
  ```json
  {
      "valid": false,
      "checked": 5,
      "errors": {
          "DB_PORT": ["The DB_PORT environment variable must be an integer."]
      }
  }
  ```
  `env:example --check` reports `missing` and `undeclared` lists instead of `errors`.
- `github` — [workflow commands](https://docs.github.com/en/actions/reference/workflow-commands-for-github-actions) that show up as annotations on the pull request. Nothing is printed when everything is valid.

```yaml
# .github/workflows/env.yml
- run: php artisan env:validate --strict --env-file=.env.production --format=github
- run: php artisan env:example --check --format=github
```

## Typed values

Environment variables arrive as strings. Call `->cast()` to get `validate()` to return them with their native PHP type, inferred from the rules:

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

Casting is off by default, so the raw validated strings are returned unless you opt in. Nullable variables that are absent stay `null`.

## Custom messages

```php
EnvValidator::make(['DB_PORT' => 'required|integer|between:1,65535'])
    ->messages([
        'DB_PORT.between' => 'DB_PORT must be a valid TCP port (1-65535).',
    ])
    ->validate();
```

In Laravel, put them under `messages` in `config/env-validator.php`.

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

To validate an explicit array instead of the live environment — handy for tests, or for a `.env` you've parsed yourself:

```php
$valid = EnvValidator::make(['DB_PORT' => 'required|integer'])
    ->using(['DB_PORT' => '5432'])
    ->passes(); // true
```

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

## Why not another package?

Other Laravel packages validate `.env` files too — for example [mathiasgrimm/laravel-env-validator](https://packagist.org/packages/mathiasgrimm/laravel-env-validator) (last release 2016), [melihovv/laravel-env-validator](https://packagist.org/packages/melihovv/laravel-env-validator) (2020) and [teun/laravel-environment-config-validator](https://packagist.org/packages/teun/laravel-environment-config-validator). `env-validator` is built for current Laravel (10–12) and PHP 8.1+, and adds things you usually have to script yourself: per-environment rules, `.env.example` generation with a CI drift check, strict mode, typed results and a framework-free core.

## Testing

```bash
composer install
composer test
```

## Roadmap

Ideas under consideration:

- A production safety preset (`APP_DEBUG`, `APP_KEY`, placeholder values).
- Masking values of variables marked as sensitive in error output.

Have an idea or a missing rule helper? Open an issue — contributions are welcome.

## Contributing

Issues and pull requests are welcome. Please add a test for any behaviour change.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
