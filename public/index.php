<?php

    define('ROOT_DIR',dirname(realpath(__DIR__)) . DIRECTORY_SEPARATOR);

    define('SRC_DIR', ROOT_DIR . 'src' . DIRECTORY_SEPARATOR);

    define('CONFIG_DIR', ROOT_DIR . 'configs' . DIRECTORY_SEPARATOR);

    define('LOGS_DIR', ROOT_DIR . 'logs' . DIRECTORY_SEPARATOR);

    define('CACHES_DIR', ROOT_DIR . 'caches' . DIRECTORY_SEPARATOR);

    define('ROUTER_DIR', ROOT_DIR . 'router' . DIRECTORY_SEPARATOR);

    define('PUBLIC_DIR', ROOT_DIR . 'public' . DIRECTORY_SEPARATOR);

    date_default_timezone_set('PRC');

    if( ! file_exists(ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
        throw new RuntimeException('Run \'composer.phar install\' in root dir');
    }

    require_once ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    require_once CONFIG_DIR . 'bootstrap.php';

