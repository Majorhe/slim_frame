<?php

namespace controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use units\AppUnits;

class CommonController
{
    public $container ;

    public function __construct (ContainerInterface $ci)
    {
        $this->container = $ci;
    }

    public function index(Request $request , Response $response)
    {
        //首页清除缓存
        AppUnits::clearRedisCache();
        return $response->withJson(AppUnits::rtnMsg(200));
    }

}