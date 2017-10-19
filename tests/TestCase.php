<?php

namespace DbExporter\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Load the service provider.
     *
     * @author Leitato Albert <wizqydy@gmail.com>
     **/
    protected function getPackageProviders($app)
    {
        return ['Elimuswift\DbExporter\DbExportHandlerServiceProvider'];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => 'testing',
            'prefix' => '',
        ]);
    }

    /**
     * Bootstrap the test environment.
     **/
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__.'/migrations'),
        ]);
    }

    /**
     * Test running migration.
     *
     * @test
     */
    public function testRunningMigration()
    {
        $users = \DB::table('testbench_users')->where('id', '=', 1)->first();
        $this->assertEquals('hello@orchestraplatform.com', $users->email);
        $this->assertTrue(\Hash::check('123', $users->password));
    }

    /**
     * Cleanup after the migration has been run.
     *
     * @author
     **/
    public function tearDown()
    {
        $this->artisan('migrate:rollback');
    }
}
