<?php

namespace Tests\Feature;

use App\Http\Controllers\EmailController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

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

    public function testEmptyEmailTemplateFieldsAreSaved(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('templates', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();
        });

        DB::table('templates')->insert([
            'name' => 'weekly_invoice',
            'subject' => 'Existing subject',
            'body' => 'Existing body',
        ]);

        $response = (new EmailController())->updateEmails(Request::create('/emails/update', 'POST', [
            'weekly_invoice__subject' => null,
            'weekly_invoice__body' => null,
        ]));

        $this->assertSame(url('/emails'), $response->getTargetUrl());
        $this->assertSame('', DB::table('templates')->value('subject'));
        $this->assertSame('', DB::table('templates')->value('body'));
    }
}
