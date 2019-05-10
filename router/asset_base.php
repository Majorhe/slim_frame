<?php

    /**
     * 公共路由文件
     */

    /**
     * 首页路由
     */
    $app->get('/index',\controller\CommonController::class . ':index')->setName('common/index');

    require_once 'asset_admin.php';
