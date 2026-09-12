<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(302);
    }

    public function testAdminMoonListSearchesAllColumns(): void
    {
        $this->actingAs(new User(['name' => 'Test User', 'avatar' => '/avatar.png']));

        $html = view('moons.list', ['moons' => collect()])->render();

        $this->assertMatchesRegularExpression(
            '/<input[^>]+id="moon-search"[^>]+data-column="all"[^>]*>/',
            $html
        );
        $this->assertSame(1, substr_count($html, 'type="text"'));
    }
}
