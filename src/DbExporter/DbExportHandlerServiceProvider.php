<?php

namespace Elimuswift\DbExporter;

use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class DbExportHandlerServiceProvider extends ServiceProvider
{
    /**
     * Defer loading of services provided by db-exporter.
     *
     * @var bool
     **/
    protected $defer = true;

    /**
     * @var DbMigrations
     */
    protected $migrator;

    /**
     * @var DbSeeding
     */
    protected $seeder;
    /**
     * @var DbExportHandler
     */
    protected $handler;

    public function boot(DbMigrations $migrator)
    {
        $this->publishes(
            [
             realpath(__DIR__.'/../').'/config/db-exporter.php' => config_path('db-exporter.php'),
            ],
            'config'
        );

        $this->mergeConfigFrom(
            realpath(__DIR__.'/../').'/config/db-exporter.php',
            'db-exporter'
        );
        // Instatiate a new DbMigrations class to send to the handler
        $this->migrator = $migrator;
         // Load the alias
        $this->loadAlias();
    }

//end boot()

    public function register()
    {
        $this->app->register(DbMigrationsServiceProvider::class);
        // Register the base export handler class
        $this->registerDbExportHandler();
         // Handle the artisan commands
        $this->registerCommands();
    }

//end register()

    public function provides()
    {
        return array('DbExporter');
    }

//end provides()

    /**
     * Register the needed commands.
     */
    public function registerCommands()
    {
        $commands = [
                     'Migrations',
                     'Seeds',
                     'Backup',
                    ];

        foreach ($commands as $command) {
            $this->{"register{$command}Command"}();
        }

        // Once the commands are registered in the application IoC container we will
        // register them with the Artisan start event so that these are available
        // when the Artisan application actually starts up and is getting used.
        $this->commands('db-exporter.migrations', 'db-exporter.seeds', 'db-exporter.backup');
    }

//end registerCommands()

    /**
     * Register the migrations command.
     */
    protected function registerMigrationsCommand()
    {
        $this->app->singleton(
            'db-exporter.migrations',
            function ($app) {
                return new Commands\MigrationsGeneratorCommand($app[DbExportHandler::class]);
            }
        );
    }

//end registerMigrationsCommand()

    /**
     * Register the seeds command.
     */
    protected function registerSeedsCommand()
    {
        $this->app->singleton(
            'db-exporter.seeds',
            function ($app) {
                return new Commands\SeedGeneratorCommand($app[DbExportHandler::class]);
            }
        );
    }

//end registerSeedsCommand()

    protected function registerBackupCommand()
    {
        $this->app->singleton(
            'db-exporter.backup',
            function () {
                return new Commands\CopyToRemoteCommand(new Server());
            }
        );
    }

//end registerBackupCommand()

    /**
     * Register the Export handler class.
     */
    protected function registerDbExportHandler()
    {
        $this->app->bind(
            DbExportHandler::class,
            function ($app) {
                // Instatiate a new DbSeeding class to send to the handler
                $seeder = new DbSeeding($app[DbMigrations::class]->database);

                // Instantiate the handler
                return new DbExportHandler($app[DbMigrations::class], $seeder);
            }
        );

        $this->app->bind(
            'DbExporter',
            function ($app) {
                return $app[DbExportHandler::class];
            }
        );
    }

//end registerDbExportHandler()

    /**
     * Load the alias = One less install step for the user.
     */
    protected function loadAlias()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('DbExporter', Facades\DbExportHandler::class);
    }

//end loadAlias()
}//end class
