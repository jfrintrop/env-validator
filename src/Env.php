<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator;

/**
 * Reads environment variables from a source and applies the same string-to-type
 * conversions Laravel's own env() helper uses, so that rules like `boolean` and
 * `integer` behave the way you expect against values that come in as strings.
 *
 * Recognised keywords (case-insensitive): true, false, null, empty, and their
 * parenthesised forms (true), (false), (null), (empty). Quoted values keep their
 * inner content verbatim.
 */
final class Env
{
    /**
     * @param array<string, mixed>|null $source When null, reads from the live
     *                                           environment ($_ENV, $_SERVER, getenv()).
     */
    public function __construct(private readonly ?array $source = null)
    {
    }

    /**
     * @param array<string, mixed> $source
     */
    public static function from(array $source): self
    {
        return new self($source);
    }

    /**
     * Get a single variable, cast to its native type, or $default when unset.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->raw($key);

        if ($raw === null) {
            return $default;
        }

        return $this->cast($raw);
    }

    /**
     * @param array<int, string> $keys
     *
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * The variable names in an explicit source, or null when reading the live
     * environment (whose keys include unrelated system variables).
     *
     * @return array<int, string>|null
     */
    public function keys(): ?array
    {
        return $this->source === null ? null : array_keys($this->source);
    }

    private function raw(string $key): ?string
    {
        if ($this->source !== null) {
            $value = $this->source[$key] ?? null;

            return $value === null ? null : (string) $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return (string) $_SERVER[$key];
        }

        $value = getenv($key);

        return $value === false ? null : $value;
    }

    private function cast(string $value): mixed
    {
        $lower = strtolower($value);

        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $this->unquote($value),
        };
    }

    private function unquote(string $value): string
    {
        $length = strlen($value);

        if ($length > 1
            && (($value[0] === '"' && $value[$length - 1] === '"')
                || ($value[0] === "'" && $value[$length - 1] === "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
