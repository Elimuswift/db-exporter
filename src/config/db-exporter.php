<?php

return [
    'backup' => [
        /*
        * The disk where your files will be backed up
        **/
        'disk' => 'local',

        /*
        * Location on disk where to backup migratons
        **/
        'migrations' => 'backup/migrations/',

        /*
        * Location on disk where to backup seeds
        **/
        'seeds' => 'backup/seeds/',
    ],
    'export_path' => [
        'migrations' => database_path('backup/migrations'),
        'seeds' => database_path('backup/seeds'),
    ],
];
