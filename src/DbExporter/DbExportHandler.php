<?php

namespace Elimuswift\DbExporter;

class DbExportHandler
{
    /**
     * @var DbMigrations
     */
    protected $migrator;

    /**
     * @var DbSeeding
     */
    protected $seeder;

    /**
     * Inject the DbMigrations class.
     *
     * @param DbMigrations $DbMigrations
     * @param DbSeeding    $DbSeeding
     */
    public function __construct(DbMigrations $DbMigrations, DbSeeding $DbSeeding)
    {
        $this->migrator = $DbMigrations;
        $this->seeder = $DbSeeding;
    }

//end __construct()

    /**
     * Create migrations from the given DB.
     *
     * @param string null $database
     *
     * @return $this
     */
    public function migrate($database = null)
    {
        $this->migrator->convert($database)->write();

        return $this;
    }

//end migrate()

    /**
     * @param null $database
     *
     * @return $this
     */
    public function seed($database = null)
    {
        $this->seeder->convert($database)->write();

        return $this;
    }

//end seed()

    /**
     * Helper function to generate the migration and the seed in one command.
     *
     * @param null $database
     *
     * @return $this
     */
    public function migrateAndSeed($database = null)
    {
        // Run the migrator generator
        $this->migrator->convert($database)->write();

        // Run the seeder generator
        $this->seeder->convert($database)->write();

        return $this;
    }

//end migrateAndSeed()

    /**
     * Add tables to the ignore array.
     *
     * @param $tables
     *
     * @return $this
     */
    public function ignore(...$tables)
    {
        DbExporter::$ignore = array_merge(DbExporter::$ignore, (array) $tables);

        return $this;
    }

//end ignore()

    /**
     * @return mixed
     */
    public function getMigrationsFilePath()
    {
        return DbMigrations::$filePath;
    }

//end getMigrationsFilePath()

    public function uploadTo($remote)
    {
        DbExporter::$remote = $remote;

        return $this;
    }

//end uploadTo()
}//end class
