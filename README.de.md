    # Env Validator

[English](README.md) | **Deutsch**

[![tests](https://github.com/jfrintrop/env-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/jfrintrop/env-validator/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/jfrintrop/env-validator.svg)](https://packagist.org/packages/jfrintrop/env-validator)
[![License](https://img.shields.io/github/license/jfrintrop/env-validator.svg)](LICENSE)

Validiere deine Umgebungsvariablen gegen ein Schema – **schnell scheitern beim Booten oder in der CI**, mit den Laravel-Validierungsregeln, die du bereits kennst.

Eine fehlende oder falsch typisierte Umgebungsvariable sollte beim Deployment laut knallen – nicht erst drei Bildschirme tief in einem Nutzer-Flow. Mit `env-validator` legst du genau fest, welche Variablen deine App braucht, welche Typen sie haben und welche Werte erlaubt sind – und prüfst das, bevor irgendetwas anderes läuft.

```php
EnvValidator::make([
    'APP_ENV'   => 'required|in:local,staging,production',
    'APP_DEBUG' => 'required|boolean',
    'APP_URL'   => 'required|url',
    'DB_PORT'   => 'required|integer|between:1,65535',
    'MAIL_FROM' => 'nullable|email',
])->validate();
```

Wenn etwas nicht stimmt, bekommst du eine klare, gesammelte Fehlermeldung statt einer späten Überraschung:

```
Environment validation failed with 2 problems:
  - The APP_ENV environment variable must be one of: local, staging, production.
  - The DB_PORT environment variable must be an integer.
```

## Warum

- **Die Syntax, die du schon kennst.** Unter der Haube arbeiten Laravel-Validierungsregeln – `required`, `in`, `boolean`, `integer`, `url`, `email`, `between`, `regex`, `nullable` und alle weiteren. Keine neue DSL zu lernen.
- **Typbewusst für Umgebungsvariablen.** Umgebungsvariablen kommen als Strings an. `APP_DEBUG=false` wird korrekt als Boolean verstanden; `null`-/`empty`-Schlüsselwörter und Werte in Anführungszeichen werden genauso behandelt wie vom `env()`-Helper von Laravel.
- **Überall schnell scheitern.** Validiere automatisch beim Booten für Web-Requests, oder nutze `php artisan env:validate` als einzeiliges CI-Gate mit korrektem Exit-Code.
- **Funktioniert eigenständig.** Kein Framework nötig – der Kern baut seinen eigenen Validator, du kannst ihn also in jedem PHP-Projekt nutzen, nicht nur in Laravel.
- **Klein und fokussiert.** Eine Aufgabe, gut gemacht. Kein Konfigurations-Wildwuchs.

**[Live-Demo →](https://jfrintrop.github.io/env-validator/)**

## Warum nicht ein anderes Paket?

Andere Laravel-Pakete validieren ebenfalls `.env`-Dateien – zum Beispiel
[mathiasgrimm/laravel-env-validator](https://packagist.org/packages/mathiasgrimm/laravel-env-validator)
(letztes Release 2016), [melihovv/laravel-env-validator](https://packagist.org/packages/melihovv/laravel-env-validator)
(2020) und [teun/laravel-environment-config-validator](https://packagist.org/packages/teun/laravel-environment-config-validator).
`env-validator` ist für aktuelles Laravel (10–12) und PHP 8.1+ gebaut und bringt Dinge mit,
die man sonst selbst skripten muss:

- **Regeln pro Umgebung** – z. B. `STRIPE_KEY` nur in `production` Pflicht.
- **`.env.example`-Generierung *und* CI-Drift-Check** aus demselben Schema.
- **Strict-Modus**, um Tippfehler und überzählige Variablen zu finden.
- **Typisierte Ergebnisse** (`int`, `float`, `bool`) per `->cast()`.
- **Framework-freier Kern** – auch außerhalb von Laravel nutzbar.

## Installation

```bash
composer require jfrintrop/env-validator
```

Benötigt PHP 8.1+ und ist kompatibel mit Laravel 10, 11 und 12. Die Testsuite läuft gegen Laravel 12 – Laravel 10 und 11 sind außerhalb ihres aktiven Support-Zeitraums, ihre letzten öffentlichen Releases lassen sich in der CI daher nicht mehr frisch installieren, das Paket selbst funktioniert aber in bestehenden Projekten mit diesen Versionen. In einer Laravel-App wird der Service Provider automatisch erkannt – du musst nichts weiter einrichten.

## Verwendung in Laravel

### 1. Konfiguration veröffentlichen

```bash
php artisan vendor:publish --tag=env-validator-config
```

### 2. Schema deklarieren

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

Nur die aufgeführten Schlüssel werden geprüft; alle anderen Umgebungsvariablen bleiben unangetastet.

### 3. Validieren

Als CI-Gate oder Pre-Deploy-Check:

```bash
php artisan env:validate
```

```
  INFO  Environment is valid (5 variables checked).
```

Bei einem Fehler gibt der Befehl jedes Problem aus und beendet sich mit Exit-Code `1`, sodass deine Pipeline stoppt:

```
  ERROR  Environment validation failed:

  ⇂ The APP_ENV environment variable must be one of: local, staging, production.
  ⇂ The DB_PORT environment variable must be an integer.
```

### Automatisch beim Booten validieren

Setze `validate_on_boot` auf `true` (oder `ENV_VALIDATOR_ON_BOOT=true`), und eine fehlerhafte Umgebung schlägt bei Web-Requests sofort fehl – bevor deine App irgendeine Arbeit erledigt.

Konsolenbefehle werden **standardmäßig übersprungen**, damit dich eine kaputte `.env` nicht aus artisan aussperrt, während du sie reparierst. Setze `validate_in_console` auf `true`, wenn die Prüfung auch für Befehle laufen soll.

### Eine `.env.example` generieren

Halte deine `.env.example` mit dem Schema synchron – generiere sie aus denselben Regeln:

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

Jede Variable erhält einen Kommentar mit ihren Einschränkungen und einen sinnvollen Platzhalter (den ersten erlaubten Wert bei `in:`-Regeln, `false` bei Booleans). Optionen:

```bash
php artisan env:example --print              # ausgeben statt eine Datei zu schreiben
php artisan env:example --path=.env.dist     # an anderen Ort schreiben
php artisan env:example --force              # vorhandene Datei überschreiben
```

### `.env.example` vor Drift schützen

Lass in der CI den Build fehlschlagen, wenn die `.env.example` nicht mehr genau die
Variablen deines Schemas auflistet:

```bash
php artisan env:example --check
php artisan env:example --check --path=.env.dist
```

```
  ERROR  .env.example is out of sync with the schema:

  ⇂ DB_PORT is declared in the schema but missing from the file.
  ⇂ OLD_VAR is in the file but not declared in the schema.
```

Verglichen werden nur Variablennamen; von Hand angepasste Platzhalter und Kommentare
bleiben unberührt. Bei Drift ist der Exit-Code `1`.

## Verwendung ohne Laravel

Der Kern hat keine Framework-Abhängigkeit:

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

### Ein explizites Array validieren

Praktisch für Tests oder um eine selbst geparste `.env` zu prüfen:

```php
$valid = EnvValidator::make(['DB_PORT' => 'required|integer'])
    ->using(['DB_PORT' => '5432'])
    ->passes(); // true
```

## Umgebungsspezifische Regeln

Manche Regeln ergeben nur in einer Umgebung Sinn – Production sollte nie mit
`APP_DEBUG=true` laufen, und ein Live-Zahlungsschlüssel ist nur dort Pflicht. Lege
zusätzliche Regeln pro Umgebung fest; sie gelten, wenn `APP_ENV` passt, und
**ersetzen** die Basisregel derselben Variable:

```php
// config/env-validator.php
'rules' => [
    'APP_ENV'    => 'required|in:local,staging,production',
    'APP_DEBUG'  => 'required|boolean',
    'STRIPE_KEY' => 'nullable|string',
],

'environments' => [
    'production' => [
        'APP_DEBUG'  => 'required|declined',              // muss false sein
        'STRIPE_KEY' => 'required|starts_with:sk_live_',
    ],
],
```

Ohne Laravel:

```php
EnvValidator::make([...])
    ->whenEnvironment(['staging', 'production'], ['SENTRY_DSN' => 'required|url'])
    ->environmentKey('APP_ENV') // der Standard
    ->validate();
```

Variablen, die nur in einem Umgebungsblock vorkommen, gelten für den Strict-Modus und
`env:example` trotzdem als deklariert.

## Strict-Modus

Der Strict-Modus fängt das umgekehrte Problem ab: Variablen, die *vorhanden, aber nicht
im Schema deklariert* sind – Tippfehler, Copy-Paste-Überbleibsel, Konfigurationsdrift. Er
braucht eine explizite Quelle (die laufende Umgebung enthält fremde Systemvariablen wie
`PATH`), übergib also die zu prüfende Menge über `using()`:

```php
EnvValidator::make([
    'APP_ENV' => 'required|in:local,production',
    'DB_PORT' => 'required|integer',
])->using([
    'APP_ENV' => 'production',
    'DB_PORT' => '5432',
    'DB_PROT' => '5432',   // Tippfehler – nicht im Schema
])->strict()->validate();
// EnvValidationException: The DB_PROT environment variable is not declared in the schema.
```

In Laravel prüfst du deine `.env`-Datei direkt per CLI gegen das Schema – ideal
als CI-Check:

```bash
php artisan env:validate --strict                 # prüft .env
php artisan env:validate --strict --env-file=.env.production
```

Das prüft sowohl die Werte *als auch*, dass die Datei keine Variable enthält, die in
deinem Schema fehlt.

## Typisierte Werte

Umgebungsvariablen kommen als Strings an. Rufe `->cast()` auf, damit `validate()`
sie mit ihrem nativen PHP-Typ zurückgibt, abgeleitet aus den Regeln:

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
- `numeric` → `int`, wenn der Wert eine ganze Zahl ist, sonst `float`
- `boolean` → `bool`

Casting ist standardmäßig deaktiviert; ohne Opt-in werden die rohen, validierten
Strings zurückgegeben. Nullable-Variablen, die fehlen, bleiben `null`.

## API

| Methode | Beschreibung |
| --- | --- |
| `EnvValidator::make(array $rules)` | Erstellt einen Validator für das angegebene Schema. |
| `->using(array $source)` | Validiert gegen dieses Array statt gegen die laufende Umgebung. |
| `->cast(bool $cast = true)` | Gibt von `validate()` typisierte Werte zurück (`int`, `float`, `bool`). |
| `->strict(bool $strict = true)` | Schlägt auch bei Quell-Variablen fehl, die nicht im Schema deklariert sind (benötigt `using()`). |
| `->whenEnvironment(string\|array $envs, array $rules)` | Wendet zusätzliche Regeln nur an, wenn die aktuelle Umgebung passt. |
| `->environments(array $map)` | Registriert mehrere Umgebungs-Regelsätze auf einmal (nach Name). |
| `->environmentKey(string $key)` | Variable mit dem Umgebungsnamen (Standard `APP_ENV`). |
| `->addRules(array $rules)` | Fügt dem Schema zusätzliche Regeln hinzu. |
| `->messages(array $messages)` | Überschreibt Standard-Fehlermeldungen (Schlüssel `VAR.rule` oder `rule`). |
| `->validate(): array` | Validiert; gibt die validierten Werte zurück, wirft bei Fehlern `EnvValidationException`. |
| `->passes(): bool` / `->fails(): bool` | Boolesche Prüfung ohne Exception. |
| `->errors(): array` | Die Fehlermeldungen, nach Variablenname gruppiert. |
| `->example(?string $header): string` | Rendert das Schema als `.env.example`-Inhalt. |
| `->exampleDrift(string $contents): array` | Vergleicht eine vorhandene `.env.example` mit dem Schema (`missing` / `undeclared`). |

### Eigene Meldungen

```php
EnvValidator::make(['DB_PORT' => 'required|integer|between:1,65535'])
    ->messages([
        'DB_PORT.between' => 'DB_PORT must be a valid TCP port (1-65535).',
    ])
    ->validate();
```

## Tests

```bash
composer install
composer test
```

## Roadmap

Ideen in Überlegung:

- Ein Production-Safety-Preset (`APP_DEBUG`, `APP_KEY`, Platzhalterwerte).
- `--format=json|github`-Ausgabe für CI-Annotationen.
- Maskierung von Werten als sensibel markierter Variablen in Fehlermeldungen.

Du hast eine Idee oder einen fehlenden Regel-Helper? Eröffne ein Issue – Beiträge sind willkommen.

## Mitwirken

Issues und Pull Requests sind willkommen. Bitte füge für jede Verhaltensänderung einen Test hinzu.

## Lizenz

The MIT License (MIT). Siehe [LICENSE](LICENSE).
