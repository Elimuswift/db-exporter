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

        // Instatiate a new DbSeeding class to send to the handler
        $this->seeder = new DbSeeding($migrator->database);

        // Instantiate the handler
        $this->handler = new DbExportHandler($this->migrator, $this->seeder);
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
        $this->app->bind('db-exporter:migrations', function()
        {
            return new Commands\MigrationsGeneratorCommand($this->handler);
        });
    }

    /**
     * Register the seeds command
     */
    protected function registerSeedsCommand()
    {
        $this->app->bind('db-exporter:seeds', function()
        {
            return new Commands\SeedGeneratorCommand($this->handler);
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
        $this->app['DbExportHandler'] = $this->app->share(function()
        {
            return $this->handler;
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