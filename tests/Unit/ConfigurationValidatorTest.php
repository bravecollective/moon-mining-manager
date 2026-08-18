<?php

namespace Tests\Unit;

use App\Exceptions\InvalidConfigurationException;
use App\Support\ConfigurationValidator;
use PHPUnit\Framework\TestCase;

class ConfigurationValidatorTest extends TestCase
{
    public function test_it_accepts_present_configuration_values(): void
    {
        (new ConfigurationValidator())->validate([
            'DB_HOST' => 'database',
            'DB_PASSWORD' => 'secret',
            'FEATURE_FLAG' => false,
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_it_reports_all_missing_configuration_values(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The application is incorrectly configured. Missing required environment variables: '
            . 'DB_HOST, EVEONLINE_CLIENT_ID, TAX_CORPORATION_ID.'
        );

        (new ConfigurationValidator())->validate([
            'DB_HOST' => null,
            'DB_DATABASE' => 'moon_mining_manager',
            'EVEONLINE_CLIENT_ID' => '',
            'TAX_CORPORATION_ID' => '   ',
        ]);
    }
}
