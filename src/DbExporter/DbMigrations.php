<?php 
namespace Elimuswift\DbExporter;

use Config;
use File;
use Illuminate\Support\Str;
use Elimuswift\DbExporter\Exceptions\InvalidDatabaseException;


class DbMigrations extends DbExporter
{
    protected $database;

    protected $selects = array(
        'column_name as Field',
        'column_type as Type',
        'is_nullable as Null',
        'column_key as Key',
        'column_default as Default',
        'extra as Extra',
        'data_type as Data_Type'
    );
    protected $constraints = array(
        'key_column_usage.table_name as Table',
        'key_column_usage.column_name as Field',
        'key_column_usage.referenced_table_name as ON',
        'key_column_usage.referenced_column_name as References',
        'REFERENTIAL_CONSTRAINTS.UPDATE_RULE as onUpdate',
        'REFERENTIAL_CONSTRAINTS.DELETE_RULE as onDelete',
    );

    protected $schema;

    protected $customDb = false;

    public static $filePath;

    /**
     * Set the database name
     * @param String $database
     * @throw InvalidDatabaseException
     */
    function __construct($database)
    {
        if (empty($database)) {
            throw new InvalidDatabaseException('No database set in app/config/database.php');
        }

        $this->database = $database;
    }

    /**
     * Write the prepared migration to a file
     */
    public function write()
    {
        // Check if convert method was called before
        // If not, call it on default DB
        if (!$this->customDb) {
            $this->convert();
        }

        $schema = $this->compile();
        $filename = date('Y_m_d_His') . "_create_" . $this->database . "_database.php";
        static::$filePath = Config::get('db-exporter.export_path.migrations')."{$filename}";

        file_put_contents(static::$filePath, $schema);

        return static::$filePath;
    }

    /**
     * Convert the database to migrations
     * If none is given, use de DB from condig/database.php
     * @param null $database
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
            if (in_array($value['table_name'], self::$ignore)) {
                continue;
            }

            $down = "Schema::drop('{$value['table_name']}');";
            $up = "Schema::create('{$value['table_name']}', function(Blueprint $" . "table) {\n";
           

            $tableDescribes = $this->getTableDescribes($value['table_name']);
            // Loop over the tables fields
            foreach ($tableDescribes as $values) {
                $method = "";
                $para = strpos($values->Type, '(');
                $type = $para > -1 ? substr($values->Type, 0, $para) : $values->Type;
                $numbers = "";
                $nullable = $values->Null == "NO" ? "" : "->nullable()";
                $default = empty($values->Default) ? "" : "->default(\"{$values->Default}\")";
                $unsigned = strpos($values->Type, "unsigned") === false ? '' : '->unsigned()';

                switch ($type) {
                    case 'int' :
                        $method = 'integer';
                        break;
                    case 'smallint' :
                        $method = 'smallInteger';
                        break;
                    case 'bigint' :
                        $method = 'bigInteger';
                        break;
                    case 'char' :
                    case 'varchar' :
                        $para = strpos($values->Type, '(');
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'string';
                        break;
                    case 'float' :
                        $method = 'float';
                        break;
                    case 'double' :
                        $para = strpos($values->Type, '('); # 6
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'double';
                        break;
                    case 'decimal' :
                        $para = strpos($values->Type, '(');
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'decimal';
                        break;
                    case 'tinyint' :
                        $method = 'boolean';
                        break;
                    case 'date' :
                        $method = 'date';
                        break;
                    case 'timestamp' :
                        $method = 'timestamp';
                        break;
                    case 'datetime' :
                        $method = 'dateTime';
                        break;
                    case 'longtext' :
                        $method = 'longText';
                        break;
                    case 'mediumtext' :
                        $method = 'mediumText';
                        break;
                    case 'text' :
                        $method = 'text';
                        break;
                    case 'longblob':
                    case 'blob' :
                        $method = 'binary';
                        break;
                    case 'enum' :
                        $method = 'enum';
                        $para = strpos($values->Type, '('); # 4
                        $options = substr($values->Type, $para + 1, -1);
                        $numbers = ', array(' . $options . ')';
                        break;
                }

                if ($values->Key == 'PRI') {
                    $method = 'increments';
                }

                $up .= "                $" . "table->{$method}('{$values->Field}'{$numbers}){$nullable}{$default}{$unsigned};\n";
            }

            $tableIndexes = $this->getTableIndexes($value['table_name']);
            if (!is_null($tableIndexes) && count($tableIndexes)){
            	foreach ($tableIndexes as $index) {
                    if(Str::endsWith($index['Key_name'], '_index'))
                	   $up .= '                $' . "table->index('" . $index['Key_name'] . "');\n";
                    }
            	}

            $up .= "            });\n\n";
            $Constraint = $ConstraintDown = "";
            $tableConstraints = $this->getTableConstraints($value['table_name']);
            if (!is_null($tableConstraints) && count($tableConstraints)){
            $Constraint = $ConstraintDown = "
            Schema::table('{$value['table_name']}', function(Blueprint $" . "table) {\n";
            $tables = [];
                foreach ($tableConstraints as $foreign) {
                    if(!in_array($foreign->Field, $tables)){
                        $ConstraintDown .= '                $' . "table->dropForeign('" . $foreign->Field. "');\n";
                        $Constraint .= '                $' . "table->foreign('" . $foreign->Field. "')->references('" . $foreign->References . "')->on('" . $foreign->ON. "')->onDelete('" . $foreign->onDelete. "');\n";
                        $tables[$foreign->Field] = $foreign->Field;
                    }
                }
                $Constraint .= "            });\n\n";
                $ConstraintDown .= "            });\n\n";
            }
            $this->schema[$value['table_name']] = array(
                'up'   => $up,
                'constraint' => $Constraint,
                'constraint_down' => $ConstraintDown,
                'down' => $down
            );
        }

        return $this;
    }

    /**
     * Compile the migration into the base migration file
     * TODO use a template with seacrh&replace
     * @return string
     */
    protected function compile()
    {
        $upSchema = "";
        $downSchema = "";
        $upConstraint = "";
        $downConstraint = "";

        // prevent of failure when no table
        if (!is_null($this->schema) && count($this->schema)) {
	        foreach ($this->schema as $name => $values) {
	            // check again for ignored tables
	            if (in_array($name, self::$ignore)) {
	                continue;
	            }
	            $upSchema .= "
	    /**
	     * Table: {$name}
	     */
	    {$values['up']}";
                $upConstraint.="
                {$values['constraint']}";
                 $downConstraint.="
                {$values['constraint_down']}";

	            $downSchema .= "
	            {$values['down']}";
	        }
        }

        // Grab the template
        $template = File::get(__DIR__ . '/templates/migration.txt');

        // Replace the classname
        $template = str_replace('{{name}}', "Create" . Str::camel(Str::title($this->database)) . "Database", $template);

        // Replace the up and down values
        $template = str_replace('{{up}}', $upSchema, $template);
        $template = str_replace('{{down}}', $downSchema, $template);
        $template = str_replace('{{upCon}}', $upConstraint, $template);
        $template = str_replace('{{downCon}}', $downConstraint, $template);

        return $template;
    }

}
