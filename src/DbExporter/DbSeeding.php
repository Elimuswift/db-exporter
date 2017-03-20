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

        $seed = $this->compile();
        $absolutePath = Config::get('db-exporter.export_path.seeds');
        $this->filename = ucfirst(Str::camel($this->database)).'DatabaseSeeder';
        $this->makePath($absolutePath);
        file_put_contents($absolutePath."/{$this->filename}.php", $seed);
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

        $stub = '';
        // Loop over the tables
        foreach ($tables as $key => $value) {
            // Do not export the ignored tables
            if (in_array($value['table_name'], self::$ignore)) {
                continue;
            }

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
        DB::table('".$tableName."')->insert([
            {$insertStub}
        ]);";
            }
        }//end foreach

        $this->seedingStub = $stub;

        return $this;
    }

//end convert()

    /**
     * Compile the current seedingStub with the seed template.
     *
     * @return mixed
     */
    protected function compile()
    {
        // Grab the template
        $template = File::get(__DIR__.'/stubs/seed.stub');

        // Replace the classname
        $template = str_replace('{{className}}', ucfirst(Str::camel($this->database)).'DatabaseSeeder', $template);
        $template = str_replace('{{run}}', $this->seedingStub, $template);

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
