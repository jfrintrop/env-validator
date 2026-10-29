<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator;

/**
 * Machine-readable renderings of a validation result, for CI pipelines.
 */
final class Report
{
    public const TEXT = 'text';

    public const JSON = 'json';

    public const GITHUB = 'github';

    public static function isSupported(string $format): bool
    {
        return in_array($format, [self::TEXT, self::JSON, self::GITHUB], true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function json(bool $valid, array $data = []): string
    {
        return json_encode(
            array_merge(['valid' => $valid], $data),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * GitHub Actions workflow commands, one `::error` annotation per message.
     *
     * @param array<int, string> $messages
     */
    public static function github(array $messages, ?string $file = null, string $title = 'env-validator'): string
    {
        $properties = ($file !== null ? 'file='.self::escapeProperty($file).',' : '')
            .'title='.self::escapeProperty($title);

        return implode("\n", array_map(
            static fn (string $message): string => '::error '.$properties.'::'.self::escapeData($message),
            $messages,
        ));
    }

    private static function escapeData(string $value): string
    {
        return str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $value);
    }

    private static function escapeProperty(string $value): string
    {
        return str_replace([':', ','], ['%3A', '%2C'], self::escapeData($value));
    }
}
