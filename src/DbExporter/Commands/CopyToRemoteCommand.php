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

    protected $commandOptions;

    public function __construct()
    {
        parent::__construct();
    }

//end __construct()

    public function fire()
    {
        $succes = $this->handleOptions();
        if ($succes) {
            // Inform what files have been uploaded
            foreach ($this->uploadedFiles as $type => $files) {
                $this->line("\n");
                $this->info(ucfirst($type));
                foreach ($files as $file) {
                    $this->sectionMessage($type, $file . ' uploaded.');
                }
            }

            $this->blockMessage('Success!', 'Everything uploaded!');
        }
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

    private function getRemoteName()
    {
        // Use default production key
        if (!$this->argument('remote')) {
            return 'production';
        } else {
            return $this->argument('remote');
        }
    }

//end getRemoteName()

    private function handleOptions()
    {
        $options = $this->option();
        switch ($options) {
        case ($options['seeds'] === true) && ($options['migrations'] === true):
            if (!$this->upload('migrations')) {
                return false;
            }

            return $this->upload('seeds');

        case $options['migrations'] === true:
            $this->commandOptions = 'migrations';

            return $this->upload('migrations');

        case $options['seeds'] === true:
            $this->commandOptions = 'seeds';

            return $this->upload('seeds');
        }
    }

//end handleOptions()

    private function upload($what)
    {
        $localPath = Config::get('db-exporter.export_path.' . $what);
        $dir = scandir($localPath);
        $remotePath = Config::get('db-exporter.remote.' . $what);
        $this->line("\n");
        $this->info(ucfirst($what));
        // Prepare the progress bar
        $filesCount = (count($dir) - count($this->ignoredFiles));
        $progress = $this->output->createProgressBar($filesCount);
        foreach ($dir as $file) {
            if (in_array($file, $this->ignoredFiles)) {
                continue;
            }

            // Capture the uploaded files for displaying later
            $this->uploadedFiles[$what][] = $remotePath . $file;

            // Copy the files
            Storage::disk($this->getDiskName())->put(
                $remotePath . $file,
                $localPath . '/' . $file
            );
            $progress->advance();
        }

        $progress->finish();

        return true;
    }

//end upload()

    private function getDiskName()
    {
        // For now static from he config file.
        return Config::get('db-exporter.remote.disk');
    }

//end getDiskName()
}//end class
