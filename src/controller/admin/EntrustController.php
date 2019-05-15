<?php

namespace controller\admin;


use models\EntrustModel;
use Slim\Http\Request;
use Slim\Http\Response;
use units\AppUnits;

class EntrustController
{

    /**
     * 委案列表
     *
     * @param Request $request
     * @param Response $response
     *
     * @param pageSize      每页显示条数
     * @param pageNum       当前页数
     * @param status        车辆状态：   1--已委托， 2--申请中，3--执行中，4--已撤销，5--已完成，6--委托失败
     * @param search        搜索关键字
     * @param orderField    排序字段： entrustPrice（委托金额）， expireDays（逾期天数）， addTime（发布时间）
     * @param orderRule     排序规则：1为正序 2为倒叙
     *
     * @return Response
     */
    public function entrustList(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            $params = array_merge(['pageSize' => 10, 'pageNum' => 1], $params);

            if (isset($params['orderField']) && isset($params['orderRule'])) {
                if (!in_array($params['orderField'], ['entrust_price', 'expire_days', 'add_time'])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }

                if (!in_array($params['orderRule'], [1, 2])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }
            }

            if (isset($params['status'])) {
                if ($params['status'] != -2) {
                    $params = array_merge(AppUnits::convertCarStatus2rewardStatus($params['status']), $params);
                }
                unset($params['status']);
            }

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getEntrustList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
            }

            if (!empty($listData['arrays'])) {
                foreach ($listData['arrays'] as $index => $entrust) {
                    $listData['arrays'][$index]['carStatus'] = AppUnits::convertRewardStatus2carStatus($entrust['carRewardStatus'], $entrust['status']);
                }
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $listData));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


    /**
     * 获取委案详情信息
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function detail(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            if (!isset($params['carEntrustId']) || empty($params['carEntrustId'])) {
                return $response->withJson(AppUnits::rtnMsg(201, '请求参数错误'));
            }

            $entrustModel = new EntrustModel();

            $data = $entrustModel->getEntrustInfo($params);

            if (isset($data['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $data['error']));
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $data['info']));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @param step 上传步骤： 1: 上传excel文件， 2: 数据验证，保存数据
     *
     * @return Response
     */
    public function batchImport(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            if (!isset($params['step']) || !in_array($params['step'], [1, 2])) {
                return $response->withJson(AppUnits::rtnMsg(201, '参数错误，上传委案失败'));
            }

            $entrustModel = new EntrustModel();

            switch ($params['step']) {
                case 1:
                    // 第一步： 上传excel文件
                    $files = $request->getUploadedFiles();
                    if (empty($files['file'])) {
                        return $response->withJson(AppUnits::rtnMsg(201, '导入失败！未获取到上传的文件！'));
                    }
                    $file = $files['file'];
                    if ($file->getError()) {
                        return $response->withJson(AppUnits::rtnMsg(201, '导入失败！未获取到上传的文件！'));
                    }

                    $result = $entrustModel->uploadFile($file);
                    if (!$result['success']) {
                        return $response->withJson(AppUnits::rtnMsg(201, $result['msg']));
                    }
                    return $response->withJson(AppUnits::rtnMsg(200, null, $result['data']));

                case 2:
                    // 数据验证
                    if (!isset($params['types']) || !in_array($params['types'], [1, 2])) {
                        return $response->withJson(AppUnits::rtnMsg(201, '请选择上传方式'));
                    }

                    if (!isset($params['filename']) || empty($params['filename'])) {
                        return $response->withJson(AppUnits::rtnMsg(201, '导入失败！未获取到上传的文件！'));
                    }

                    $data = $entrustModel->checkEntrustData($params['filename']);

                    if (!$data['success']) {
                        return $response->withJson(AppUnits::rtnMsg(201, $data['msg']));
                    }

                    if (!empty($data['data']['validData'])) {
                        $result = $entrustModel->setPost()->batchImport(['model' => $data['data']['validData'], 'types' => $params['types']]);

                        if (isset($result['error'])) {
                            return $response->withJson(AppUnits::rtnMsg(201, $result['error']));
                        }
                    }

                    $responseData = ['successTotal' => count($data['data']['validData']), 'failureTotal' => count($data['data']['invalidData'])];

                    if (!empty($data['data']['invalidData'])) {
                        $filename = isset($result['batch']) ? $result['batch'] . '.xlsx' : date('YmdHis') . AppUnits::rand_str() . '.xlsx';
                        $result = $entrustModel->writerExcel($data['data']['invalidData'], $filename, 'import_failure');
                        if ($result['success']) {
                            $responseData['url'] = $result['data']['url'];
                        }
                    }

                    sleep(10);

                    return $response->withJson(AppUnits::rtnMsg(200, '', $responseData));

                default:
                    break;
            }
            return $response->withJson(AppUnits::rtnMsg(201, '参数错误，上传委案失败'));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


    /**
     * 上传历史列表
     *
     * @param Request $request
     * @param Response $response
     *
     * @param pageSize      每页显示条数
     * @param pageNum       当前页数
     * @param search        搜索关键字
     * @param orderField    排序字段： releaseTotal（发布数量）， addTotal（新增数量）， replaceTotal（替换数量）， successTotal（成功数量）， failureTotal（失败数量），addTime（发布时间）
     * @param orderRule     排序规则：1为正序 2为倒叙
     *
     * @return Response
     */
    public function historyList(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            $params = array_merge(['pageSize' => 10, 'pageNum' => 1], $params);

            if (isset($params['orderField']) && isset($params['orderRule'])) {
                if (!in_array($params['orderField'], ['releaseTotal', 'addTotal', 'replaceTotal', 'successTotal', 'failureTotal', 'addTime'])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }

                if (!in_array($params['orderRule'], [1, 2])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }
            }

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getHistoryList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $listData));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


    /**
     * 上传历史详情
     *
     * @param Request $request
     * @param Response $response
     *
     * @param carEntrustBatchId 委托批次ID
     * @param pageSize          每页显示条数
     * @param pageNum           当前页数
     * @param search            搜索关键字
     * @param orderField        排序字段： entrustPrice（委托金额）， expireDays（逾期天数）
     * @param orderRule         排序规则：1为正序 2为倒叙
     *
     * @return Response
     */
    public function historyDetail(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            if (!isset($params['carEntrustBatchId']) || empty($params['carEntrustBatchId'])) {
                return $response->withJson(AppUnits::rtnMsg(201, '委案批次ID不能为空'));
            }

            $params = array_merge(['pageSize' => 10, 'pageNum' => 1], $params);

            if (isset($params['orderField']) && isset($params['orderRule'])) {
                if (!in_array($params['orderField'], ['entrustPrice', 'expireDays'])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }

                if (!in_array($params['orderRule'], [1, 2])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }
            }

            if (isset($params['status'])) {
                if ($params['status'] != -2) {
                    $params = array_merge(AppUnits::convertCarStatus2rewardStatus($params['status']), $params);
                }
                unset($params['status']);
            }

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getCarList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
            }

            if (!empty($listData['list'])) {
                foreach ($listData['list'] as $index => $entrust) {
                    $listData['list'][$index]['carStatus'] = AppUnits::convertRewardStatus2carStatus($entrust['rewardStatus'], $entrust['entrustStatus']);
                }
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $listData));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


    /**
     * 导出委案
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function exportEntrust(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            if (!isset($params['carEntrustBatchId']) || empty($params['carEntrustBatchId'])) {
                return $response->withJson(AppUnits::rtnMsg(201, '委案批次ID不能为空'));
            }

            $params['pageSize'] = 1000000;

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getCarList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
            }

            $result = $entrustModel->writerExcel($listData['list']);

            if (!$result['success']) {
                return $response->withJson(AppUnits::rtnMsg(201, $result['msg']));
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $result['data']));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


    /**
     * 委案审批详情
     *
     * @param Request $request
     * @param Response $response
     *
     * @param carEntrustBatchId     委托批次ID
     * @param pageSize              每页显示条数
     * @param pageNum               当前页数
     * @param search                搜索关键字
     * @param rewardStatus          车辆状态（多选）：8--待分析， 0--待发布， 1--已发布， 2--申请中， 3--已授权， 4--可执行， 7--待确认， 9--待付款， 10--待回款， 5--已完成， 6--已失效， 11--已委托, 多个状态用逗号隔开
     * @param entrustStatus         委案状态（单选）：1--为已委托， 2--为委托失败， 3--为已激活， 4--为激活失败
     * @param gpsStatus             GPS状态（单选）：0--为无效， 1--为有效
     * @param assetsType            资产类型（多选）：
     * @param isProviderGpsUserful  贴G状态（单选）：1--为无效， 2--为有效
     *
     * @return Response
     */
    public function approvalDetail(Request $request, Response $response)
    {
        try {
            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));


            if (!isset($params['carEntrustBatchId']) || empty($params['carEntrustBatchId'])) {
                return $response->withJson(AppUnits::rtnMsg(201, '委案批次ID不能为空'));
            }

            $params = array_merge(['pageSize' => 10, 'pageNum' => 1], $params);

            if (isset($params['rewardStatus']) && $params['rewardStatus'] == -2) {
                unset($params['rewardStatus']);
            }

            if (isset($params['entrustStatus']) && $params['entrustStatus'] == -2) {
                unset($params['entrustStatus']);
            }

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->approvalDetail($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $listData));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


}
