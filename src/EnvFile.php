<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator;

/**
 * Minimal reader for dotenv-style files: `KEY=value` lines, `export KEY=value`,
 * and `#` comments. Values are returned verbatim (quotes included) — pass them
 * through {@see Env} semantics via EnvValidator::using() to normalise them.
 */
final class EnvFile
{
    /**
     * @return array<string, string>
     */
    public static function parse(string $contents): array
    {
        $values = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if (str_starts_with($key, 'export ')) {
                $key = trim(substr($key, 7));
            }

            $values[$key] = trim($value);
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(string $contents): array
    {
        return array_keys(self::parse($contents));
    }
}
