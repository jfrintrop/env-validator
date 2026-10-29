<?php

declare(strict_types=1);

/*
 * Default validation messages, phrased for environment variables. The :attribute
 * placeholder is filled with the variable name verbatim (e.g. APP_ENV). Users can
 * override any of these per call via EnvValidator::messages([...]).
 *
 * This is a focused subset of the rules that make sense for env values; any rule
 * not listed here falls back to Laravel's own default message when available.
 */

return [
    'required' => 'The :attribute environment variable is required but missing.',
    'boolean' => 'The :attribute environment variable must be a boolean (true/false, 1/0).',
    'accepted' => 'The :attribute environment variable must be enabled (true, 1, yes, on).',
    'declined' => 'The :attribute environment variable must be disabled (false, 0, no, off).',
    'integer' => 'The :attribute environment variable must be an integer.',
    'numeric' => 'The :attribute environment variable must be a number.',
    'string' => 'The :attribute environment variable must be a string.',
    'url' => 'The :attribute environment variable must be a valid URL.',
    'email' => 'The :attribute environment variable must be a valid email address.',
    'ip' => 'The :attribute environment variable must be a valid IP address.',
    'json' => 'The :attribute environment variable must be a valid JSON string.',
    'uuid' => 'The :attribute environment variable must be a valid UUID.',
    'alpha' => 'The :attribute environment variable may only contain letters.',
    'alpha_dash' => 'The :attribute environment variable may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute environment variable may only contain letters and numbers.',
    'starts_with' => 'The :attribute environment variable must start with one of: :values.',
    'ends_with' => 'The :attribute environment variable must end with one of: :values.',
    'regex' => 'The :attribute environment variable has an invalid format.',
    'in' => 'The :attribute environment variable must be one of: :values.',
    'not_in' => 'The :attribute environment variable must not be one of: :values.',
    'min' => [
        'numeric' => 'The :attribute environment variable must be at least :min.',
        'string' => 'The :attribute environment variable must be at least :min characters.',
    ],
    'max' => [
        'numeric' => 'The :attribute environment variable must not be greater than :max.',
        'string' => 'The :attribute environment variable must not be greater than :max characters.',
    ],
    'between' => [
        'numeric' => 'The :attribute environment variable must be between :min and :max.',
        'string' => 'The :attribute environment variable must be between :min and :max characters.',
    ],
    'digits' => 'The :attribute environment variable must be :digits digits.',
    'digits_between' => 'The :attribute environment variable must be between :min and :max digits.',
    'required_if' => 'The :attribute environment variable is required when :other is :value.',
    'required_with' => 'The :attribute environment variable is required when :values is present.',
    'required_without' => 'The :attribute environment variable is required when :values is not present.',
];
