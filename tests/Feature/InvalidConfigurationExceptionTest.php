<?php

namespace Tests\Feature;

use App\Exceptions\InvalidConfigurationException;
use Illuminate\Http\Request;
use Tests\TestCase;

class InvalidConfigurationExceptionTest extends TestCase
{
    public function test_it_renders_a_clear_html_configuration_error(): void
    {
        $response = (new InvalidConfigurationException(['DB_HOST', 'EVEONLINE_CLIENT_ID']))
            ->render(Request::create('/admin'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Application incorrectly configured', $response->getContent());
        $this->assertStringContainsString('DB_HOST', $response->getContent());
        $this->assertStringContainsString('EVEONLINE_CLIENT_ID', $response->getContent());
    }

    public function test_it_renders_a_clear_json_configuration_error(): void
    {
        $request = Request::create('/admin', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = (new InvalidConfigurationException(['DB_HOST']))->render($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([
            'message' => 'The application is incorrectly configured. Missing required environment variables: DB_HOST.',
            'missing' => ['DB_HOST'],
        ], $response->getData(true));
    }
}
