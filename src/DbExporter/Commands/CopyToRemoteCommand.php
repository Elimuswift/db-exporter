<?php

namespace Elimuswift\DbExporter\Commands;

use Config;
use Storage;
use Symfony\Component\Console\Input\InputOption;

class CopyToRemoteCommand extends GeneratorCommand
{
    protected $name = 'db-exporter:backup';

    protected $description = 'Command to copy the migrations and/or the seeds to a remote host.';

    protected $ignoredFiles = [
                               '..',
                               '.',
                               '.gitkeep',
                              ];

    protected $uploadedFiles;

    protected static $filesCount;

    public function __construct()
    {
        parent::__construct();
    }


    public function fire()
    {
        $this->handleOptions();
        foreach ($this->uploadedFiles as $type => $files) {
            $this->line("\n");
            $this->info(ucfirst($type));
            foreach ($files as $file) {
                $this->sectionMessage($type, $file.' uploaded.');
            }
        }

        $disk = $this->getDiskName();
        $this->blockMessage('Success!', "Everything uploaded to $disk filesystem!");
    }

//end fire()

    protected function getOptions()
    {
        return [
                [
                 'migrations',
                 'm',
                 InputOption::VALUE_NONE,
                 'Upload the migrations to a storage.',
                 null,
                ],
                [
                 'seeds',
                 's',
                 InputOption::VALUE_NONE,
                 'Upload the seeds to the remote host.',
                 null,
                ],
               ];
    }

//end getOptions()

    protected function handleOptions()
    {
        $options = $this->option();
        foreach ($options as $key => $value) {
            if ($value) {
                $this->upload($key);
            }
        }
    }

    protected function upload($what)
    {
        $localPath = Config::get('db-exporter.export_path.'.$what);
        $dir = scandir($localPath);
        $remotePath = Config::get('db-exporter.remote.'.$what);
        $this->line("\n");
        $this->info(ucfirst($what));
        // Reset file coounter
        static::$filesCount = 0;
        // Prepare the progress bar
        array_walk($dir, function ($file) {
            if ($this->ignoredFile($file)) {
                return;
            }
            ++static::$filesCount;
        });
        $progress = $this->output->createProgressBar(static::$filesCount);
        foreach ($dir as $file) {
            if ($this->ignoredFile($file)) {
                $this->comment("---> ignoring $file ");
                continue;
            }

            // Capture the uploaded files for displaying later
            $this->uploadedFiles[$what][] = $remotePath.$file;

            // Copy the files
            Storage::disk($this->getDiskName())->put(
                $remotePath.$file,
                $localPath.'/'.$file
            );
            $progress->advance();
        }

        $progress->finish();

        return true;
    }

//end upload()

    protected function getDiskName()
    {
        // For now static from he config file.
        return Config::get('db-exporter.backup.disk');
    }

    /**
     * Determine if a file should be ignored.
     *
     * @param string $file filename
     *
     * @return bool
     **/
    protected function ignoredFile($file)
    {
        if (in_array($file, $this->ignoredFiles)) {
            return true;
        }

        return false;
    }

//end getDiskName()
}//end class
