<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Jfrintrop\EnvValidator\Exceptions\EnvValidationException;

/**
 * Validates environment variables against a schema expressed in Laravel
 * validation rule syntax.
 *
 * Works standalone (plain PHP) or inside Laravel — it builds its own validator
 * factory, so it never depends on the framework container being booted.
 *
 *   EnvValidator::make([
 *       'APP_ENV'   => 'required|in:local,staging,production',
 *       'APP_DEBUG' => 'required|boolean',
 *       'DB_PORT'   => 'required|integer|between:1,65535',
 *   ])->validate();
 */
final class EnvValidator
{
    /** @var array<string, string|array<int, mixed>> */
    private array $rules;

    /** @var array<string, string> */
    private array $messages = [];

    private ?Env $env = null;

    private bool $cast = false;

    private bool $strict = false;

    private ?ValidatorContract $lastValidator = null;

    /**
     * @param array<string, string|array<int, mixed>> $rules
     */
    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     */
    public static function make(array $rules): self
    {
        return new self($rules);
    }

    /**
     * Replace the full rule set.
     *
     * @param array<string, string|array<int, mixed>> $rules
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Add or override individual rules without discarding the rest.
     *
     * @param array<string, string|array<int, mixed>> $rules
     */
    public function addRules(array $rules): self
    {
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /**
     * Override one or more default error messages.
     *
     * @param array<string, string> $messages
     */
    public function messages(array $messages): self
    {
        $this->messages = array_merge($this->messages, $messages);

        return $this;
    }

    /**
     * Validate against an explicit array instead of the live environment.
     *
     * @param array<string, mixed> $source
     */
    public function using(array $source): self
    {
        $this->env = Env::from($source);

        return $this;
    }

    /**
     * Cast validated values to their native PHP type based on the rules
     * (`integer`/`int` → int, `numeric` → int|float, `boolean` → bool).
     * Off by default so the raw validated strings are returned.
     */
    public function cast(bool $cast = true): self
    {
        $this->cast = $cast;

        return $this;
    }

    /**
     * Also fail when the source contains variables not declared in the schema
     * (typo and stray-variable detection). Requires an explicit source via
     * using() — the live environment holds unrelated system variables.
     */
    public function strict(bool $strict = true): self
    {
        $this->strict = $strict;

        return $this;
    }

    /**
     * Validate and return the validated values. Throws on failure.
     *
     * @return array<string, mixed>
     *
     * @throws EnvValidationException
     */
    public function validate(): array
    {
        $validator = $this->runValidator();

        $errors = array_merge($validator->errors()->messages(), $this->strictErrors());

        if ($errors !== []) {
            throw EnvValidationException::withErrors($errors);
        }

        $validated = $validator->validated();

        return $this->cast ? $this->castValues($validated) : $validated;
    }

    /**
     * Render this schema as the contents of a `.env.example` file.
     */
    public function example(?string $header = null): string
    {
        return EnvExampleGenerator::fromRules($this->rules)->generate($header);
    }

    public function passes(): bool
    {
        return $this->runValidator()->passes() && $this->strictErrors() === [];
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return array_merge($this->runValidator()->errors()->messages(), $this->strictErrors());
    }

    /**
     * Errors for variables present in the source but absent from the schema.
     *
     * @return array<string, array<int, string>>
     *
     * @throws \LogicException when strict mode is used against the live environment
     */
    private function strictErrors(): array
    {
        if (! $this->strict) {
            return [];
        }

        $keys = ($this->env ?? new Env())->keys();

        if ($keys === null) {
            throw new \LogicException(
                'Strict mode requires an explicit source via using(); the live environment '
                .'contains system variables that are not part of your schema.'
            );
        }

        $errors = [];
        foreach (array_diff($keys, array_keys($this->rules)) as $key) {
            $errors[$key] = [sprintf('The %s environment variable is not declared in the schema.', $key)];
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @return array<string, mixed>
     */
    private function castValues(array $validated): array
    {
        foreach ($validated as $key => $value) {
            if ($value === null) {
                continue;
            }

            $names = $this->ruleNames($key);

            if (in_array('integer', $names, true) || in_array('int', $names, true)) {
                $validated[$key] = (int) $value;
            } elseif (in_array('boolean', $names, true)) {
                $validated[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array('numeric', $names, true)) {
                $validated[$key] = $this->toNumber($value);
            }
        }

        return $validated;
    }

    /**
     * Lower-cased rule names for a single key (without their parameters).
     *
     * @return array<int, string>
     */
    private function ruleNames(string $key): array
    {
        $ruleSet = $this->rules[$key] ?? [];
        $parts = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

        $names = [];
        foreach ((array) $parts as $part) {
            if (is_string($part)) {
                $names[] = strtolower(trim(explode(':', $part, 2)[0]));
            }
        }

        return $names;
    }

    private function toNumber(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $string = (string) $value;

        return preg_match('/^[+-]?\d+$/', $string) === 1 ? (int) $string : (float) $string;
    }

    private function runValidator(): ValidatorContract
    {
        $env = $this->env ?? new Env();
        $data = $env->only(array_keys($this->rules));

        $validator = $this->factory()->make($data, $this->rules, $this->messages);

        // Show variable names verbatim (APP_ENV, not "app env") in messages.
        $validator->addCustomAttributes(
            array_combine(array_keys($this->rules), array_keys($this->rules))
        );

        $this->lastValidator = $validator;

        return $validator;
    }

    private function factory(): Factory
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'validation', $this->defaultMessages());

        return new Factory(new Translator($loader, 'en'));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultMessages(): array
    {
        return require __DIR__.'/resources/messages.php';
    }
}
