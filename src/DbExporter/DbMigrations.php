<?php

namespace Elimuswift\DbExporter;

use Config;
use File;
use Illuminate\Support\Str;
use Elimuswift\DbExporter\Exceptions\InvalidDatabaseException;

class DbMigrations extends DbExporter
{
    /**
     * Column data types.
     *
     * @var string
     **/
    protected $columns = [
                            'int' => 'integer',
                            'smallint' => 'smallInteger',
                            'bigint' => 'bigInteger',
                            'char ' => 'string',
                            'varchar' => 'string',
                            'float' => 'float',
                            'double' => 'double',
                            'decimal' => 'decimal',
                            'tinyint' => 'tinyInteger',
                            'date' => 'date',
                            'timestamp' => 'timestamp',
                            'datetime' => 'dateTime',
                            'longtext' => 'longText',
                            'mediumtext' => 'mediumText',
                            'text' => 'text',
                            'longblob' => 'binary',
                            'blob' => 'binary',
                            'enum' => 'enum',
                            'char' => 'char ',
                            'geometry' => 'geometry',
                            'time' => 'time',
                            'point' => 'point',
                            'polygon' => 'polygon',
                            'multipolygon' => 'muliPolygon',
                            'multilinestring' => 'multiLineString',
                            'mulitpoint' => 'multiPoint',
                            'mediumint' => 'mediumInteger',
                            'mac' => 'macAddress',
                            'json' => 'json',
                            'linestring' => 'lineString',
                            'geometrycollection' => 'geometryCollection',
                            'bool' => 'boolean',
                            'year' => 'year',
                            ];
    /**
     * Primary key column types.
     *
     * @var array
     **/
    protected $primaryKeys = [
                        'bigint' => 'bigIncrements',
                        'int' => 'increments',
    ];

    protected $schema;

    protected $customDb = false;

    public static $filePath;

    protected $primaryKey;

    protected $defaultLength;

    protected $methodName;
    /**
     * File name for migration file.
     *
     * @var string
     */
    public $filename;

    /**
     * Set the database name.
     *
     * @param string $database
     * @throw InvalidDatabaseException
     */
    public function __construct($database)
    {
        if (empty($database)) {
            throw new InvalidDatabaseException('No database set in app/config/database.php');
        }

        $this->database = $database;
    }

    //end __construct()

    /**
     * Write the prepared migration to a file.
     */
    public function write()
    {
        // Check if convert method was called before
        // If not, call it on default DB
        if (!$this->customDb) {
            $this->convert();
        }

        $schema = $this->compile();
        $absolutePath = Config::get('db-exporter.export_path.migrations');
        $this->makePath($absolutePath);
        $this->filename = date('Y_m_d_His').'_create_'.$this->database.'_database.php';
        static::$filePath = $absolutePath."/{$this->filename}";
        file_put_contents(static::$filePath, $schema);

        return static::$filePath;
    }

    //end write()

    /**
     * Convert the database to migrations
     * If none is given, use de DB from condig/database.php.
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

        $tables = $this->getTables();

        // Loop over the tables
        foreach ($tables as $key => $value) {
            // Do not export the ignored tables
            if (in_array($value['table_name'], static::$ignore)) {
                continue;
            }

            $down = "Schema::dropIfExists('{$value['table_name']}');";
            $up = "Schema::create('{$value['table_name']}', function(Blueprint $"."table) {\n";

            $tableDescribes = $this->getTableDescribes($value['table_name']);
            // Loop over the tables fields
            foreach ($tableDescribes as $values) {
                $para = strpos($values->Type, '(');
                $type = $para > -1 ? substr($values->Type, 0, $para) : $values->Type;
                $nullable = 'NO' == $values->Nullable ? '' : '->nullable()';
                $default = empty($values->Default) || 'NULL' == $values->Default ? '' : "->default({$values->Default})";
                $default = 'CURRENT_TIMESTAMP' == $values->Default || 'current_timestamp()' == $values->Default ? '->useCurrent()' : $default;
                $unsigned = false === strpos($values->Type, 'unsigned') ? '' : '->unsigned()';
                $this->hasDefaults($type, $values);
                $this->methodName = $this->columnType($type);
                if ('PRI' == $values->Key) {
                    $this->primaryKey = '->primary()';
                    if ($methodName = $this->columnType($values->Data_Type, 'primaryKeys') && 'auto_increment' == $values->Extra) {
                        $this->primaryKey = '->autoIncrement()';
                    }
                }

                $up .= '                $'."table->{$this->methodName}('{$values->Field}'{$this->defaultLength}){$this->primaryKey}{$nullable}{$default}{$unsigned};\n";
                $this->unsetData();
            }//end foreach

            $tableIndexes = (array) $this->getTableIndexes($value['table_name']);
            if (!is_null($tableIndexes) && count($tableIndexes)) {
                foreach ($tableIndexes as $index) {
                    if (Str::endsWith(@$index['Key_name'], '_index')) {
                        $up .= '                $'."table->index('".$index['Column_name']."');\n";
                    }
                }
            }

            $up .= "            });\n\n";
            $Constraint = $ConstraintDown = '';
            /*
                * @var array
             */
            $tableConstraints = $this->getTableConstraints($value['table_name']);
            if (!is_null($tableConstraints) && $tableConstraints->count()) {
                $Constraint = $ConstraintDown = "
            Schema::table('{$value['table_name']}', function(Blueprint $"."table) {\n";
                $tables = [];
                foreach ($tableConstraints as $foreign) {
                    if (!in_array($foreign->Field, $tables)) {
                        $field = "{$foreign->Table}_{$foreign->Field}_foreign";
                        $ConstraintDown .= '                $'."table->dropForeign('".$field."');\n";
                        $Constraint .= '                $'."table->foreign('".$foreign->Field."')->references('".$foreign->References."')->on('".$foreign->ON."')->onDelete('".$foreign->onDelete."');\n";
                        $tables[$foreign->Field] = $foreign->Field;
                    }
                }

                $Constraint .= "            });\n\n";
                $ConstraintDown .= "            });\n\n";
            }

            $this->schema[$value['table_name']] = [
                                                    'up' => $up,
                                                    'constraint' => $Constraint,
                                                    'constraint_down' => $ConstraintDown,
                                                    'down' => $down,
                                                    ];
        }//end foreach

        return $this;
    }

    //end convert()

    public function columnType($type, $columns = 'columns', $method = '')
    {
        return array_key_exists($type, $this->{$columns}) ? $this->{$columns}[$type] : $method;
    }

    //end columnType()

    /**
     * Compile the migration into the base migration file
     * TODO use a template with seacrh&replace.
     *
     * @return string
     */
    protected function compile()
    {
        $upSchema = '';
        $downSchema = '';
        $upConstraint = '';
        $downConstraint = '';

        // prevent of failure when no table
        if (!is_null($this->schema) && is_array($this->schema)) {
            foreach ($this->schema as $name => $values) {
                // check again for ignored tables
                if (in_array($name, self::$ignore)) {
                    continue;
                }

                $upSchema .= "
      /**
       * Migration schema for table {$name}
         * 
       */
      {$values['up']}";
                $upConstraint .= "
                {$values['constraint']}";
                $downConstraint .= "
                {$values['constraint_down']}";

                $downSchema .= "
              {$values['down']}";
            }
        }//end if

        // Grab the template
        $template = File::get(__DIR__.'/stubs/migration.stub');

        // Replace the classname
        $template = str_replace('{{name}}', 'Create'.ucfirst(Str::camel($this->database)).'Database', $template);

        // Replace the up and down values
        $template = str_replace('{{up}}', $upSchema, $template);
        $template = str_replace('{{down}}', $downSchema, $template);
        $template = str_replace('{{upCon}}', $upConstraint, $template);
        $template = str_replace('{{downCon}}', $downConstraint, $template);

        return $template;
    }

    /**
     * summary.
     *
     * @author
     */
    public function hasDefaults($type, $column)
    {
        if ($hasSize = strpos($column->Type, '(')) {
            $values = substr($column->Type, ($hasSize + 1), -1);
            if ('enum' == $type) {
                $this->defaultLength = ', array('.$values.')';
            } elseif (in_array($type, ['char', 'varchar', 'text', 'mediumtext', 'longtext'])) {
                $this->defaultLength = ', '.$column->Length;
            } elseif (in_array($type, ['double', 'float', 'decimal'])) {
                $this->defaultLength = ", $column->Precision, $column->Scale";
            }
        }
    }

    /**
     * summary.
     *
     * @author
     */
    protected function unsetData()
    {
        $this->primaryKey = null;
        $this->methodName = null;
        $this->defaultLength = null;
    }

    //end compile()
}//end class
