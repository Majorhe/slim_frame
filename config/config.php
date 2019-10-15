<?php

return [
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => true,
        'db' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'slim',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]
    ],
    //日志记录
    'logger' => [
        'name'  => 'shark_taskmgnt',
        'level' => \Monolog\Logger::DEBUG,
        'path'  =>  LOGS_DIR  . 'taskmgnt-'. date('Y-m-d',$_SERVER['REQUEST_TIME']).'.log',
    ],
];
