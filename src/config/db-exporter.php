<?php

return array(
    'backup' => array(
        'disk' => 'local',
        'migrations' => 'backup/migrations/',
        'seeds' => 'backup/seeds/'
    ),
    'export_path' => array(
        'migrations' => database_path('backup/migrations'),
        'seeds' => database_path('seeds')
    )
);
