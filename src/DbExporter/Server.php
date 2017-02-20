<?php 
namespace Elimuswift\DbExporter;

use Config;
use Storage;

class Server
{
    protected $ignoredFiles = array('..', '.', '.gitkeep');

    public static $uploadedFiles;

    /**
     * What the class has to upload (migrations or seeds)
     * @var
     */
    protected $what;

    public function upload($what)
    {
        
        $remotePath = Config::get('db-exporter.backup.' . $what);

        foreach ($this->files($what) as $file) {
            if (in_array($file, $this->ignoredFiles)) {
                continue;
            }

            // Capture the uploaded files for display later
            self::$uploadedFiles[$what][] = $remotePath . $file;

            // Copy the files
            Storage::disk($this->getDiskName())->put(
                $remotePath .'/'.$file,
                $localPath . '/' . $file
            );
        }

        return true;
    }
    public function files($what='')
    {
        $localPath = Config::get('db-exporter.export_path.'.$what);

        return scandir($localPath);
    }

    private function getDiskName()
    {
        // For now static from he config file.
        return Config::get('db-exporter.backup.disk');
    }
}