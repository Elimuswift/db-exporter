<?php

namespace Elimuswift\DbExporter;

use DB;

abstract class DbExporter
{
    /**
     * Contains the ignore tables.
     *
     * @var array
     */
    public static $ignore = array('migrations');
    public static $remote;

    /**
     * Get all the tables.
     *
     * @return mixed
     */
    public $database;

    /**
     * Select fields.
     *
     * @var array
     **/
    protected $selects = array(
                          'column_name as Field',
                          'column_type as Type',
                          'is_nullable as Null',
                          'column_key as Key',
                          'column_default as Default',
                          'extra as Extra',
                          'data_type as Data_Type',
                         );
    /**
     * Select fields from  constraints.
     *
     * @var array
     **/
    protected $constraints = array(
                              'key_column_usage.table_name as Table',
                              'key_column_usage.column_name as Field',
                              'key_column_usage.referenced_table_name as ON',
                              'key_column_usage.referenced_column_name as References',
                              'REFERENTIAL_CONSTRAINTS.UPDATE_RULE as onUpdate',
                              'REFERENTIAL_CONSTRAINTS.DELETE_RULE as onDelete',
                             );

    protected function getTables()
    {
        $pdo = DB::connection()->getPdo();

        return $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema="'.$this->database.'"');
    }

//end getTables()

    public function getTableIndexes($table)
    {
        $pdo = DB::connection()->getPdo();

        return $pdo->query('SHOW INDEX FROM '.$this->database.'.'.$table.' WHERE Key_name != "PRIMARY"');
    }

//end getTableIndexes()

    /**
     * Get all the columns for a given table.
     *
     * @param $table
     *
     * @return array
     */
    protected function getTableDescribes($table)
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', '=', $this->database)
            ->where('table_name', '=', $table)
            ->get($this->selects);
    }

//end getTableDescribes()

    /**
     * Get all the foreign key constraints for a given table.
     *
     * @param $table
     *
     * @return array
     */
    protected function getTableConstraints($table)
    {
        return DB::table('information_schema.key_column_usage')
            ->distinct()
            ->join('information_schema.REFERENTIAL_CONSTRAINTS', 'REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME', '=', 'key_column_usage.CONSTRAINT_NAME')
            ->where('key_column_usage.table_schema', '=', $this->database)
            ->where('key_column_usage.table_name', '=', $table)
            ->get($this->constraints);
    }

//end getTableConstraints()

    /**
     * Grab all the table data.
     *
     * @param $table
     *
     * @return mixed
     */
    protected function getTableData($table)
    {
        return DB::table($this->database.'.'.$table)->get();
    }

//end getTableData()

    /**
     * Try to create directories if they dont exist.
     *
     * @param string $path
     **/
    protected function makePath($path)
    {
        $del = DIRECTORY_SEPARATOR;
        $dir = '';
        $directories = explode($del, $path);
        foreach ($directories as $directory) {
            if (!empty($directory)) {
                $dir .= $del.$directory;
            }

            if (!is_dir($dir)) {
                @mkdir($dir);
            }
        }
    }

//end makePath()

    /**
     * Write the file.
     *
     * @return mixed
     */
    abstract public function write();

    /**
     * Convert the database to a usefull format.
     *
     * @param null $database
     *
     * @return mixed
     */
    abstract public function convert($database = null);

    /**
     * Put the converted stub into a template.
     *
     * @return mixed
     */
    abstract protected function compile();
}//end class
