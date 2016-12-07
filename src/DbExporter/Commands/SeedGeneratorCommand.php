<?php 
namespace Elimuswift\DbExporter\Commands;


use Elimuswift\DbExporter\DbExporter;
use Elimuswift\DbExporter\DbExportHandler;
use Symfony\Component\Console\Input\InputOption;
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

    public function fire()
    {
        $database = $this->argument('database');

        // Display some helpfull info
        if (empty($database)) {
            $this->comment("Preparing the seeder class for database {$this->getDatabaseName()}");
        } else {
            $this->comment("Preparing the seeder class for database {$database}");
        }

        // Grab the options
        $ignore = $this->option('ignore');

        if (empty($ignore)) {
            $this->handler->seed($database);
        } else {
            $tables = explode(',', str_replace(' ', '', $ignore));
            $this->handler->ignore($tables)->seed($database);
            foreach (DbExporter::$ignore as $table) {
                $this->comment("Ignoring the {$table} table");
            }
        }

        // Symfony style block messages
        $formatter = $this->getHelperSet()->get('formatter');
        $filename = $this->getFilename();

        $errorMessages = array('Success!', "Database seed class generated in: {$filename}");

        $formattedBlock = $formatter->formatBlock($errorMessages, 'info', true);
        $this->line($formattedBlock);
    }

    private function getFilename()
    {
        $filename = Str::camel($this->getDatabaseName()) . "TableSeeder";
        return Config::get('db-exporter.export_path.seeds')."/{$filename}.php";
    }

    
}