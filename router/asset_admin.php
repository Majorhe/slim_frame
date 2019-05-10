<?php

    /**
     * 后台接口路由
     *
     */
    $app->group('/admin',function () use ($app){

        // 获取用户信息和菜单列表数据
        $app->get('/menu',\controller\admin\HomeController::class . ':getMenuList')->setName('getMenuList');

        // 获取AccessToken
        $app->get('/token', \controller\admin\HomeController::class . ':getAccessToken')->setName('getAccessToken');



    })->add(new \middleware\AdminAuth());
