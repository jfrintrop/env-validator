# Changelog

All notable changes to `env-validator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
