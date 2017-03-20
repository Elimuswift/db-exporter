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
                          'int'        => 'integer',
                          'smallint'   => 'smallInteger',
                          'bigint'     => 'bigInteger',
                          'char '      => 'string',
                          'varchar'    => 'string',
                          'float'      => 'float',
                          'double'     => 'double',
                          'decimal'    => 'decimal',
                          'tinyint'    => 'boolean',
                          'date'       => 'date',
                          'timestamp'  => 'timestamp',
                          'datetime'   => 'dateTime',
                          'longtext'   => 'longText',
                          'mediumtext' => 'mediumText',
                          'text'       => 'text',
                          'longblob'   => 'binary',
                          'blob'       => 'binary',
                          'enum'       => 'enum',
                         ];

    protected $schema;

    protected $customDb = false;

    public static $filePath;
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

    }//end __construct()


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

        $schema       = $this->compile();
        $absolutePath = Config::get('db-exporter.export_path.migrations');
        $this->makePath($absolutePath);
        $this->filename   = date('Y_m_d_His').'_create_'.$this->database.'_database.php';
        static::$filePath = $absolutePath."/{$this->filename}";
        file_put_contents(static::$filePath, $schema);

        return static::$filePath;

    }//end write()


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

            $down = "Schema::drop('{$value['table_name']}');";
            $up   = "Schema::create('{$value['table_name']}', function(Blueprint $"."table) {\n";

            $tableDescribes = $this->getTableDescribes($value['table_name']);
            // Loop over the tables fields
            foreach ($tableDescribes as $values) {
                $para     = strpos($values->Type, '(');
                $type     = $para > -1 ? substr($values->Type, 0, $para) : $values->Type;
                $numbers  = '';
                $nullable = $values->null == 'NO' ? '' : '->nullable()';
                $default  = empty($values->Default) ? '' : "->default(\"{$values->Default}\")";
                $default  = $values->Default == 'CURRENT_TIMESTAMP' ? '->useCurrent()' : $default;
                $unsigned = strpos($values->Type, 'unsigned') === false ? '' : '->unsigned()';

                if (in_array($type, ['var', 'varchar', 'enum', 'decimal', 'float'])) {
                    $para    = strpos($values->Type, '(');
                    $opt     = substr($values->Type, ($para + 1), -1);
                    $numbers = $type == 'enum' ? ', array('.$opt.')' : ',  '.$opt;
                }

                $method = $this->columnType($type);
                if ($values->Key == 'PRI') {
                    $method = 'increments';
                }

                $up .= '                $'."table->{$method}('{$values->Field}'{$numbers}){$nullable}{$default}{$unsigned};\n";
            }//end foreach

            $tableIndexes = $this->getTableIndexes($value['table_name']);
            if (!is_null($tableIndexes) && count($tableIndexes)) {
                foreach ($tableIndexes as $index) {
                    if (Str::endsWith($index['Key_name'], '_index')) {
                        $up .= '                $'."table->index('".$index['Column_name']."');\n";
                    }
                }
            }

            $up        .= "            });\n\n";
            $Constraint = $ConstraintDown = '';
            /*
                * @var array
             */
            $tableConstraints = $this->getTableConstraints($value['table_name']);
            if (!is_null($tableConstraints) && count($tableConstraints)) {
                $Constraint = $ConstraintDown = "
            Schema::table('{$value['table_name']}', function(Blueprint $"."table) {\n";
                $tables     = [];
                foreach ($tableConstraints as $foreign) {
                    if (!in_array($foreign->Field, $tables)) {
                        $ConstraintDown .= '                $'."table->dropForeign('".$foreign->Field."');\n";
                        $Constraint     .= '                $'."table->foreign('".$foreign->Field."')->references('".$foreign->References."')->on('".$foreign->ON."')->onDelete('".$foreign->onDelete."');\n";
                        $tables[$foreign->Field] = $foreign->Field;
                    }
                }

                $Constraint     .= "            });\n\n";
                $ConstraintDown .= "            });\n\n";
            }

            $this->schema[$value['table_name']] = array(
                                                   'up'              => $up,
                                                   'constraint'      => $Constraint,
                                                   'constraint_down' => $ConstraintDown,
                                                   'down'            => $down,
                                                  );
        }//end foreach

        return $this;

    }//end convert()


    public function columnType($type)
    {
        return array_key_exists($type, $this->columns) ? $this->columns[$type] : '';

    }//end columnType()


    /**
     * Compile the migration into the base migration file
     * TODO use a template with seacrh&replace.
     *
     * @return string
     */
    protected function compile()
    {
        $upSchema       = '';
        $downSchema     = '';
        $upConstraint   = '';
        $downConstraint = '';

        // prevent of failure when no table
        if (!is_null($this->schema) && count($this->schema)) {
            foreach ($this->schema as $name => $values) {
                // check again for ignored tables
                if (in_array($name, self::$ignore)) {
                    continue;
                }

                $upSchema       .= "
	    /**
	     * Migration schema for table {$name}
         * 
	     */
	    {$values['up']}";
                $upConstraint   .= "
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

    }//end compile()


}//end class
