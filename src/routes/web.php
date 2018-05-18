<?php
/**
 * Created by PhpStorm.
 * User: shasnhanpc
 * Date: 2018/5/14
 * Time: 11:32
 */

use Slim\Http\Request;
use Slim\Http\Response;


$app->get('/',  function (Request $request, Response $response, $args) {
    return call_user_func_array([new \controllers\IndexController($this), 'home'], [$request, $response, $args]);
})->setName('home');



$app->get('/test.html','\controllers\IndexController:test')->setName('test');

$app->get('/testDb.html','\controllers\IndexController:testDb')->setName('testDb');