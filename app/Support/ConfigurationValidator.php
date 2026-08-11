<?php

namespace App\Support;

use App\Exceptions\InvalidConfigurationException;

class ConfigurationValidator
{
    /**
     * @param array<string, mixed> $variables
     */
    public function validate(array $variables): void
    {
        $missing = array_keys(array_filter($variables, function ($value) {
            return $value === null || (is_string($value) && trim($value) === '');
        }));

        if ($missing) {
            throw new InvalidConfigurationException($missing);
        }
    }
}
