<?php
/**
 * Created by PhpStorm.
 * User: shasnhanpc
 * Date: 2018/5/14
 * Time: 11:21
 */
define("DS", DIRECTORY_SEPARATOR);
define("ROOT", realpath(dirname(__DIR__)) . DS);
define("VENDORDIR", ROOT . "vendor" . DS);
define("SRCDIR", ROOT . "src" . DS);
define("ROUTEDIR", ROOT . "src" . DS . "routes" . DS);
define("TEMPLATEDIR", ROOT . "templates" . DS);
define("LANGUAGEDIR", ROOT . "languages" . DS);

date_default_timezone_set("Asia/Shanghai");

// 引用autoload文件
if (file_exists(VENDORDIR . "autoload.php")) {
    require_once VENDORDIR . "autoload.php";
} else {
    die("<pre>Run 'composer.phar install' in root dir</pre>");
}

$config = require_once (SRCDIR . 'config.php');              // 引用配置文件

$app = new \Slim\App($config);

// Get container
$container = $app->getContainer();

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig(SRCDIR . 'templates', [
        'cache' => SRCDIR . 'cache'
    ]);

    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

// Service factory for the ORM
$container['db'] = function ($container) use ($config) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($config['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};


require_once (SRCDIR . 'routes' . DS . 'api.php');  // 引用api路由文件

require_once (SRCDIR . 'routes' . DS . 'web.php');  // 引用前端路由文件

$app->run();