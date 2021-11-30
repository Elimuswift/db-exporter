<?php

namespace Elimuswift\DbExporter\Commands;

use Elimuswift\DbExporter\DbExportHandler;
use Config;
use Illuminate\Support\Str;

class SeedGeneratorCommand extends GeneratorCommand
{
    protected $name = 'db-exporter:seeds';

    protected $description = 'Export your database table data to a seed class.';

    /**
     * @var \Elimuswift\DbExporter\DbExportHandler
     */
    protected $handler;

    public function __construct(DbExportHandler $handler)
    {
        parent::__construct();

        $this->handler = $handler;
    }

    //end __construct()

    public function handle()
    {
        $database = $this->argument('database');
        // Display some helpfull info
        if (empty($database)) {
            $this->comment("Preparing the seeder class for database {$this->getDatabaseName()}");
        } else {
            $this->comment("Preparing the seeder class for database {$database}");
        }

        // Grab the options
        $this->fireAction('seed', $database);
        // Symfony style block messages
        $formatter = $this->getHelperSet()->get('formatter');
        $filename = $this->getFilename();
        $errorMessages = [
                                'Success!',
                                "Database seed class generated in: {$filename}",
                                ];
        $formattedBlock = $formatter->formatBlock($errorMessages, 'info', true);
        $this->line($formattedBlock);
    }

    //end fire()

    private function getFilename()
    {
        $filename = ucfirst(Str::camel($this->database)).'DatabaseSeeder';

        return Config::get('db-exporter.export_path.seeds')."/{$filename}.php";
    }

    //end getFilename()
}//end class
