<?php 
namespace Elimuswift\DbExporter\Commands;

use Config;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GeneratorCommand extends Command
{
    /**
     * Get the database name from the app/config/database.php file
     * @return String
     */
    protected function getDatabaseName()
    {
        $connType = Config::get('database.default');
        $database = Config::get('database.connections.' .$connType );

        return $database['database'];
    }

    protected function blockMessage($title, $message, $style = 'info')
    {
        // Symfony style block messages
        $formatter = $this->getHelperSet()->get('formatter');
        $errorMessages = array($title, $message);
        $formattedBlock = $formatter->formatBlock($errorMessages, $style, true);
        $this->line($formattedBlock);
    }

    protected function sectionMessage($title, $message)
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $formattedLine = $formatter->formatSection(
            $title,
            $message
        );
        $this->line($formattedLine);
    }
    protected function getArguments()
    {
        return array(
            array('database', InputArgument::OPTIONAL, 'Override the application database')
        );
    }

    protected function getOptions()
    {
        return array(
            array('ignore', 'i', InputOption::VALUE_REQUIRED, 'Ignore tables to export, seperated by a comma', null)
        );
    }

    protected function fireAction($action,$database)
    {
        // Grab the options
        $ignore = $this->option('ignore');

        if (empty($ignore)) {
            $this->handler->$action($database);
        } else {
            $tables = explode(',', str_replace(' ', '', $ignore));

            $this->handler->ignore($tables)->$action($database);
            foreach (DbExporter::$ignore as $table) {
                $this->comment("Ignoring the {$table} table");
            }
        }
    }
}