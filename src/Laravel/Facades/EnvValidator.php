<?php

declare(strict_types=1);

namespace Jfrintrop\EnvValidator\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Jfrintrop\EnvValidator\EnvValidator as EnvValidatorService;

/**
 * @method static array validate()
 * @method static bool passes()
 * @method static bool fails()
 * @method static array errors()
 * @method static string example(?string $header = null)
 * @method static EnvValidatorService rules(array $rules)
 * @method static EnvValidatorService addRules(array $rules)
 * @method static EnvValidatorService messages(array $messages)
 * @method static EnvValidatorService using(array $source)
 * @method static EnvValidatorService cast(bool $cast = true)
 * @method static EnvValidatorService strict(bool $strict = true)
 *
 * @see EnvValidatorService
 */
final class EnvValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'env-validator';
    }
}
