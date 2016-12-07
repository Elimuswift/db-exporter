<?php

return array(
    'remote' => array(
        'name' => 'production',
        'migrations' => '/home/htdocs/testing/migrations/',
        'seeds' => '/home/htdocs/testing/seeds/'
    ),
    'export_path' => array(
        'migrations' => database_path('backup/migrations'),
        'seeds' => database_path('backup/seeds')
    )
);
