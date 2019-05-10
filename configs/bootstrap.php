<?php

    $configs = require_once CONFIG_DIR . 'config.php';

    $container = new \Slim\Container($configs);

    $container['logger'] = function () use ($configs){
        $logger = new \Monolog\Logger($configs['logger']['name']);
        $file_handler = new \Monolog\Handler\StreamHandler($configs['logger']['path'],$configs['logger']['level']);
        $logger->pushHandler($file_handler);
        return $logger;
    };

    $container['notFoundHandler'] = function () {
        return new \exception\NotFoundHandler();
    };

    $container['notAllowedHandler'] = function (){
        return new \exception\NotAllowedHandler();
    };

    $container['errorHandler'] = function () use ($configs){
        return new \exception\ErrorHandler($configs['settings']['displayErrorDetails']);
    };

    $container['phpErrorHandler'] = function () use ($configs){
        return new \exception\PhpErrorHandler($configs['settings']['displayErrorDetails']);
    };

    $app = new \Slim\App($container);

    // token和权限认证的中间组件已在router中添加
    // $app->add(new \middleware\AdminAuth());
    $app->add(new \middleware\Hook(\middleware\Hook::APP_AFTER_EXECUTE,new \hooks\BackendLogHook()));

    \units\AppUnits::setApp($app);

    require_once ROUTER_DIR . 'asset_base.php';

    try {
        $app->run();
    } catch (\Exception $exception) {
        header('Content-Type:application/json;charset=UTF-8');
        echo json_encode(\units\AppUnits::rtnMsg(201,$exception->getMessage()));
        exit(0);
    }
