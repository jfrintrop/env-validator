<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Exceptions;

use RuntimeException;

/**
 * Thrown when one or more environment variables fail validation.
 *
 * The message is a human-readable, multi-line summary suitable for a crash
 * screen or a CI log; the structured errors are available via errors().
 */
final class EnvValidationException extends RuntimeException
{
    /**
     * @param array<string, array<int, string>> $errors keyed by variable name
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct($this->buildMessage($errors));
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Flat list of every error message across all variables.
     *
     * @return array<int, string>
     */
    public function messages(): array
    {
        return array_merge(...array_values($this->errors));
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    private function buildMessage(array $errors): string
    {
        $count = array_sum(array_map('count', $errors));
        $noun = $count === 1 ? 'problem' : 'problems';

        $lines = ["Environment validation failed with {$count} {$noun}:"];

        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                $lines[] = "  - {$message}";
            }
        }

        return implode("\n", $lines);
    }
}
