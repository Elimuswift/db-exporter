<?php 
namespace Elimuswift\DbExporter;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;


class DbExportHandlerServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * @var DbMigrations $migrator
     */
    protected $migrator;

    /**
     * @var DbSeeding $seeder
     */
    protected $seeder;
    /**
     * @var DbExportHandler $handler
     */
    protected $handler;

    public function boot(DbMigrations $migrator)
    {
        // Instatiate a new DbMigrations class to send to the handler
        $this->migrator = $migrator;

        
    }

    public function register()
    {
            $this->publishes([
            realpath(__DIR__ .'/../').'/config/db-exporter.php' => config_path('db-exporter.php'),
        ]);

        $this->mergeConfigFrom(
            realpath(__DIR__ .'/../').'/config/db-exporter.php', 'db-exporter'
        );

        $this->app->register(DbMigrationsServiceProvider::class);

        // Load the classes
        $this->loadClasses();

        // Register the base export handler class
        $this->registerDbExportHandler();

        // Handle the artisan commands
        $this->registerCommands();

        // Load the alias
        $this->loadAlias();
    }

    /**
     * Load to classes
     */
    protected function loadClasses()
    {
        
    }


    public function provides()
    {
        return array('DbExportHandler');
    }

    /**
     * Register the needed commands
     */
    public function registerCommands()
    {
        $this->registerMigrationsCommand();
        $this->registerSeedsCommand();
        $this->registerRemoteCommand();
        $this->commands(
            'db-exporter:migrations',
            'db-exporter:seeds',
            'db-exporter:remote'
        );
    }

    /**
     * Register the migrations command
     */
    protected function registerMigrationsCommand()
    {
        $this->app->bind('db-exporter:migrations', function($app)
        {
            return new Commands\MigrationsGeneratorCommand($app[DbExportHandler::class]);
        });
    }

    /**
     * Register the seeds command
     */
    protected function registerSeedsCommand()
    {
        $this->app->bind('db-exporter:seeds', function($app)
        {
            return new Commands\SeedGeneratorCommand($app[DbExportHandler::class]);
        });
    }

    protected function registerRemoteCommand()
    {
        $this->app->bind('db-exporter:remote', function()
        {
            return new Commands\CopyToRemoteCommand(new Server);
        });
    }

    /**
     * Register the Export handler class
     */
    protected function registerDbExportHandler()
    {
        $this->app->bind(DbExportHandler::class, function($app)
        {
            // Instatiate a new DbSeeding class to send to the handler
        $seeder = new DbSeeding($app[DbMigrations::class]->database);

        // Instantiate the handler
        return new DbExportHandler($app[DbMigrations::class], $seeder);
        });
    }

    /**
     * Load the alias = One less install step for the user
     */
    protected function loadAlias()
    {
        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('DbExportHandler', 'Nwidart\DbExporter\Facades\DbExportHandler');
        });
    }

}