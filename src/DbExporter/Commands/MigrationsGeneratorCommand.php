<?php 
namespace Elimuswift\DbExporter\Commands;

use Config;
use Elimuswift\DbExporter\DbExporter;
use Elimuswift\DbExporter\DbExportHandler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrationsGeneratorCommand extends GeneratorCommand
{
    protected $name = 'db-exporter:migrations';

    protected $description = 'Export your database to migrations.';
    /**
     * @var \Elimuswift\DbExporter\DbExportHandler
     */
    protected $handler;

    public function __construct(DbExportHandler $handler)
    {
        parent::__construct();

        $this->handler = $handler;
    }

    public function fire()
    {
        $database = $this->argument('database');

        // Display some helpfull info
        if (empty($database)) {
            $this->comment("Preparing the migrations for database: {$this->getDatabaseName()}");
        } else {
            $this->comment("Preparing the migrations for database {$database}");
        }

        // Grab the options
        $ignore = $this->option('ignore');

        if (empty($ignore)) {
            $this->handler->migrate($database);
        } else {
            $tables = explode(',', str_replace(' ', '', $ignore));

            $this->handler->ignore($tables)->migrate($this->argument('database'));
            foreach (DbExporter::$ignore as $table) {
                $this->comment("Ignoring the {$table} table");
            }
        }

        // Symfony style block messages
        $this->blockMessage('Success!', 'Database migrations generated in: ' . $this->handler->getMigrationsFilePath());
    }

    
}