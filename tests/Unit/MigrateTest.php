<?php

namespace DBExporter\Tests\Unit;

use DbExporter\Tests\TestCase;

class MigrateTest extends TestCase
{
    /**
     * Test running migration.
     *
     * @test
     */
    public function testRunningMigration()
    {
        $this->artisan('migrate', ['--database' => 'testing']);
        $users = \DB::table('test_users')->where('id', '=', 1)->first();
        $this->assertEquals('hello@orchestraplatform.com', $users->email);
        $this->assertTrue(\Hash::check('123', $users->password));
    }
}
