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


        $app->group('/entrust', function () use ($app) {

            // 委托列表
            $app->get('/list', \controller\admin\EntrustController::class . ':entrustList')->setName('admin/entrustList');

            // 委托详情
            $app->get('/detail', \controller\admin\EntrustController::class . ':detail')->setName('admin/entrustDetail');

            // 委托导入
            $app->post('/import', \controller\admin\EntrustController::class . ':batchImport')->setName('admin/entrustBatchImport');

            // 委托导出
            $app->post('/export', \controller\admin\EntrustController::class . ':exportEntrust')->setName('admin/entrustExport');

            // 委托导入历史
            $app->get('/history', \controller\admin\EntrustController::class . ':historyList')->setName('admin/entrustImportHistory');

            // 委托导入历史详情
            $app->get('/historyDetail', \controller\admin\EntrustController::class . ':historyDetail')->setName('admin/entrustHistoryDetail');

            // 委托审批列表
            $app->get('/approvalList', \controller\admin\EntrustController::class . ':historyList')->setName('admin/entrustApprovalList');


        });



    })->add(new \middleware\AdminAuth());
