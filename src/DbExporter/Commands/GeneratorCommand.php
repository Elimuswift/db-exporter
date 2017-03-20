<?php

namespace Elimuswift\DbExporter\Commands;

use Config;
use Illuminate\Console\Command;
use Elimuswift\DbExporter\DbExporter;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GeneratorCommand extends Command
{
    /**
     * Current database being exported or migrated.
     *
     * @var string
     **/
    protected $database;

    /**
     * Get the database name from the app/config/database.php file.
     *
     * @return string
     */
    protected function getDatabaseName()
    {
        $connType = Config::get('database.default');
        $database = Config::get('database.connections.'.$connType);

        return $database['database'];
    }

//end getDatabaseName()

    protected function blockMessage($title, $message, $style = 'info')
    {
        // Symfony style block messages
        $formatter = $this->getHelperSet()->get('formatter');
        $errorMessages = array(
                           $title,
                           $message,
                          );
        $formattedBlock = $formatter->formatBlock($errorMessages, $style, true);
        $this->line($formattedBlock);
    }

//end blockMessage()

    protected function sectionMessage($title, $message)
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $formattedLine = $formatter->formatSection(
            $title,
            $message
        );
        $this->line($formattedLine);
    }

//end sectionMessage()

    protected function getArguments()
    {
        return array(
                array(
                 'database',
                 InputArgument::OPTIONAL,
                 'Override the application database',
                ),
               );
    }

//end getArguments()

    protected function getOptions()
    {
        return array(
                array(
                 'ignore',
                 'i',
                 InputOption::VALUE_REQUIRED,
                 'Ignore tables to export, seperated by a comma',
                 null,
                ),
               );
    }

//end getOptions()

    protected function fireAction($action, $database)
    {
        // Grab the options
        $ignore = $this->option('ignore');
        $this->database = $database;
        if (empty($ignore)) {
            $this->handler->$action($database);
        } else {
            $tables = explode(',', str_replace(' ', '', $ignore));
            DbExporter::$ignore = array_merge(DbExporter::$ignore, $tables);
            $this->handler->$action($database);
            foreach ($tables as $table) {
                $this->comment("Ignoring the {$table} table");
            }
        }
    }

//end fireAction()
}//end class
