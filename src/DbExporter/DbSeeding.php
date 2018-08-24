<?php

namespace Elimuswift\DbExporter;

use Config;
use DB;
use Illuminate\Support\Str;
use File;

class DbSeeding extends DbExporter
{
    /**
     * @var string
     */
    public $database;

    /**
     * @var string
     */
    public $filename;

    /**
     * @var string
     */
    protected $seedingStub;

    /**
     * @var bool
     */
    protected $customDb = false;

    /**
     * Set the database name.
     *
     * @param string $database
     */
    public function __construct($database)
    {
        $this->database = $database;
    }

//end __construct()

    /**
     * Write the seed file.
     */
    public function write()
    {
        // Check if convert method was called before
        // If not, call it on default DB
        if (!$this->customDb) {
            $this->convert();
        }

        foreach ($this->seedingStub as $table => $value) 
        {
            $seed = $this->compile($table);
            $absolutePath = Config::get('db-exporter.export_path.seeds');
            $this->filename = ucfirst(Str::camel($table)) . 'DatabaseSeeder';
            $this->makePath($absolutePath);
            file_put_contents($absolutePath . "/{$this->filename}.php", $seed);
        }
    }

//end write()

    /**
     * Convert the database tables to something usefull.
     *
     * @param null $database
     *
     * @return $this
     */
    public function convert($database = null)
    {
        if (!is_null($database)) {
            $this->database = $database;
            $this->customDb = true;
        }

        // Get the tables for the database
        $tables = $this->getTables();
        $result = [];

        // Get tables to ignore
        $config = config('db-exporter.seeds');
        $ignore_tables = collect([]);
        if(!is_null($config) && !is_null($config['ignore_tables'])) {
            $ignore_tables = collect($config['ignore_tables']);
        }

        // Loop over the tables
        foreach ($tables as $key => $value) 
        {
            if($ignore_tables->contains($value['table_name'])) {
                continue;
            }

            // Do not export the ignored tables
            if (in_array($value['table_name'], self::$ignore)) {
                continue;
            }

            $stub = '';
            $tableName = $value['table_name'];
            $tableData = $this->getTableData($value['table_name']);
            $insertStub = '';

            foreach ($tableData as $obj) {
                $insertStub .= "
            [\n";
                foreach ($obj as $prop => $value) {
                    $insertStub .= $this->insertPropertyAndValue($prop, $value);
                }

                if (count($tableData) > 1) {
                    $insertStub .= "            ],\n";
                } else {
                    $insertStub .= "            ]\n";
                }
            }

            if ($this->hasTableData($tableData)) {
                $stub .= "
        DB::table('" . $tableName . "')->insert([
            {$insertStub}
        ]);";
            }

            $result[$tableName] = $stub; 
        }//end foreach

        $this->seedingStub = $result;

        return $this;
    }

//end convert()

    /**
     * Compile the current seedingStub with the seed template.
     *
     * @return mixed
     */
    protected function compile($table)
    {
        // Grab the template
        $template = File::get(__DIR__ . '/stubs/seed.stub');

        // Replace the classname
        $template = str_replace('{{className}}', ucfirst(Str::camel($table)) . 'DatabaseSeeder', $template);
        $template = str_replace('{{run}}', $this->seedingStub[$table], $template);

        return $template;
    }

//end compile()

    private function insertPropertyAndValue($prop, $value)
    {
        $prop = addslashes($prop);
        $value = addslashes($value);

        return "                '{$prop}' => '{$value}',\n";
    }

//end insertPropertyAndValue()

    /**
     * @param $tableData
     *
     * @return bool
     */
    public function hasTableData($tableData)
    {
        return count($tableData) >= 1;
    }

//end hasTableData()
}//end class
