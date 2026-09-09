<?php

namespace Tests\Unit;

use App\Http\Controllers\EmailController;
use Illuminate\Database\Schema\Blueprint;
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
        $this->assertTrue(true);
    }

    public function testEmailTemplatePageCreatesOnlyMissingTemplates(): void
    {
        $defaultConnection = config('database.default');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        try {
            Schema::create('templates', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 100)->unique();
                $table->string('subject', 255);
                $table->text('body');
                $table->timestamps();
            });

            DB::table('templates')->insert([
                'name' => 'receipt',
                'subject' => 'Existing subject',
                'body' => 'Existing body',
            ]);

            $controller = new EmailController();
            $controller->showEmails();
            $controller->showEmails();

            $this->assertSame(5, DB::table('templates')->count());
            $this->assertSame(
                [
                    'receipt',
                    'renter_invoice',
                    'renter_notification',
                    'renter_reminder',
                    'weekly_invoice',
                ],
                DB::table('templates')->orderBy('name')->pluck('name')->all()
            );
            $this->assertSame('Existing subject', DB::table('templates')->where('name', 'receipt')->value('subject'));
            $this->assertSame('Existing body', DB::table('templates')->where('name', 'receipt')->value('body'));
        } finally {
            DB::purge('sqlite');
            DB::setDefaultConnection($defaultConnection);
        }
    }
}
