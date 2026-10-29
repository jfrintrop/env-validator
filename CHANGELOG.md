# Changelog

All notable changes to `env-validator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0]

### Added
- Environment-specific rules: `EnvValidator::whenEnvironment()`, `environments()`
  and `environmentKey()`, plus an `environments` key in the Laravel config. Rules
  apply when `APP_ENV` matches and replace the base rule for that variable.
- `php artisan env:example --check` and `EnvValidator::exampleDrift()` to fail CI
  when `.env.example` drifts from the schema.

### Changed
- Default messages for the `accepted` and `declined` rules.
- `env:example` and strict mode also include variables declared only in an
  environment block.

## [1.3.0]

### Added
- `EnvValidator::strict()` to also fail on source variables not declared in the
  schema (requires an explicit `using()` source).
- `php artisan env:validate --strict [--env-file=.env]` to lint an env file
  against the schema, including stray/undeclared variables.

## [1.2.0]

### Added
- `EnvValidator::cast()` to return typed values from `validate()` — `integer`/`int`
  → `int`, `numeric` → `int`|`float`, `boolean` → `bool`. Off by default.

## [1.1.0]

### Added
- `php artisan env:example` command and `EnvValidator::example()` to generate a
  `.env.example` file from the schema (with `--print`, `--path`, `--force`).

## [1.0.0]

### Added
- Initial release.
- `EnvValidator` core with Laravel validation rule syntax, usable standalone.
- Env-aware type casting (booleans, `null`/`empty` keywords, quoted values).
- Laravel service provider with config publishing and auto-discovery.
- `php artisan env:validate` command with CI-friendly exit codes.
- Optional fail-fast validation on boot.
- `EnvValidator` facade.
