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
        $this->publishes([
            realpath(__DIR__ .'/../').'/config/db-exporter.php' => config_path('db-exporter.php'),
        ]);

        $this->mergeConfigFrom(
            realpath(__DIR__ .'/../').'/config/db-exporter.php', 'db-exporter'
        );
        // Instatiate a new DbMigrations class to send to the handler
        $this->migrator = $migrator;
         // Load the alias
        $this->loadAlias();
             // Handle the artisan commands
        $this->registerCommands();

        
    }

    public function register()
    {
        $this->app->register(DbMigrationsServiceProvider::class);
        // Register the base export handler class
        $this->registerDbExportHandler();
    }

    public function provides()
    {
        return array('DbExporter');
    }

    /**
     * Register the needed commands
     */
    public function registerCommands()
    {
        $this->registerMigrationsCommand();
        $this->registerSeedsCommand();
        $this->registerRemoteCommand();
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
        $this->app->bind('db-exporter:backup', function()
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

        $this->app->bind('DbExporter', function($app){
            return $app[DbExportHandler::class];
        });
    }

    /**
     * Load the alias = One less install step for the user
     */
    protected function loadAlias()
    {

            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('DbExporter', Facades\DbExportHandler::class);
    }

}