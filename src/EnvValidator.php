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
     * Validate and return the validated values. Throws on failure.
     *
     * @return array<string, mixed>
     *
     * @throws EnvValidationException
     */
    public function validate(): array
    {
        $validator = $this->runValidator();

        if ($validator->fails()) {
            throw EnvValidationException::withErrors($validator->errors()->messages());
        }

        return $validator->validated();
    }

    public function passes(): bool
    {
        return $this->runValidator()->passes();
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
        return $this->runValidator()->errors()->messages();
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
