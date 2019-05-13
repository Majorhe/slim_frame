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
     * @param search        搜索关键字
     * @param orderField    排序字段： entrustPrice（委托金额）， expireDays（逾期天数）， addTime（发布时间）
     * @param orderRule     排序规则：1为正序 2为倒叙
     *
     * @return Response
     */
    public function entrustList(Request $request, Response $response)
    {
        try {

            $listData = [
                "arrays" => [
                    [
                        "carEntrustId"=>"fd64f4as6df749a68d4",
                        "carFundProvider"=>"一嗨",
                        "carNo"=>"浙J12345",
                        "carFrameNo"=>"abcefg",
                        "expireDays"=>"32",
                        "entrustPrice"=>1234,
                        "gpsStatus"=>1,
                        "addTime"=>145678765432,
                        "addManger"=>"admin123",
                        "carOwnerName"=>"张三",
                        "carOwnerPhone"=>"18769265916",
                        "carOwnerIdno"=>"7228261889327492",
                        "carBrand"=>"雪弗兰",
                        "carColor" => "black"
                    ],
                    [
                        "carEntrustId"=>"fd64f4as6df749a68d4",
                        "carFundProvider"=>"一嗨",
                        "carNo"=>"浙J12345",
                        "carFrameNo"=>"abcefg",
                        "expireDays"=>"32",
                        "entrustPrice"=>1234,
                        "gpsStatus"=>1,
                        "addTime"=>145678765432,
                        "addManger"=>"admin123",
                        "carOwnerName"=>"张三",
                        "carOwnerPhone"=>"18769265916",
                        "carOwnerIdno"=>"7228261889327492",
                        "carBrand"=>"雪弗兰",
                        "carColor" => "black"
                    ],
                    [
                        "carEntrustId"=>"fd64f4as6df749a68d4",
                        "carFundProvider"=>"一嗨",
                        "carNo"=>"浙J12345",
                        "carFrameNo"=>"abcefg",
                        "expireDays"=>"32",
                        "entrustPrice"=>1234,
                        "gpsStatus"=>1,
                        "addTime"=>145678765432,
                        "addManger"=>"admin123",
                        "carOwnerName"=>"张三",
                        "carOwnerPhone"=>"18769265916",
                        "carOwnerIdno"=>"7228261889327492",
                        "carBrand"=>"雪弗兰",
                        "carColor" => "black"
                    ]
                ],
                "page" => [
                    "currentPage"=> 1,
                    "pages"=> 120,
                    "perNum"=> 10,
                    "total"=> 1196
                ]
            ];

            return $response->withJson(AppUnits::rtnMsg(200, null, $listData));


            $params = AppUnits::paramsFilter(AppUnits::decryptWithOpenssl($request->getParam('contents')));

            $params = array_merge(['pageSize' => 10, 'pageNum' => 1], $params);

            if (isset($params['orderField']) && isset($params['orderRule'])) {
                if (!in_array($params['orderField'], ['entrust_price（', 'expire_days', 'add_time'])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }

                if (!in_array($params['orderRule'], [1, 2])) {
                    return $response->withJson(AppUnits::rtnMsg(201, '排序参数错误'));
                }
            }

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getEntrustList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
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
            $data = [
                "expireDays" => 32,
                "entrustPrice" => 213,
                "gpsStatus" => 1,
                "gpsTypes" =>  "www.gpsLoginType.com",
                "gpsAccount" =>  "1762891024",
                "gpsPasswd" =>  "1234sun",
                "deliveryPlace" =>  "浙江",
                "remark" =>  "ememememem"
            ];

            return $response->withJson(AppUnits::rtnMsg(200, null, $data));


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
                    return $response->withJson(AppUnits::rtnMsg(201, null, $result['data']));

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
                        $result = $entrustModel->batchImport($data['data']['validData']);

                        if (isset($result['error'])) {
                            return $response->withJson(AppUnits::rtnMsg(201, $result['error']));
                        }
                    }

                    $responseData = ['successTotal' => count($data['data']['invalidData']), 'failureTotal' => count($data['data']['invalidData'])];

                    if (!empty($data['data']['invalidData'])) {
                        $result = $entrustModel->writerExcel($data['data']['invalidData']);
                        if ($result['success']) {
                            $responseData['url'] = $result['data']['url'];
                        }
                    }

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

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getCarList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
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

            $entrustModel = new EntrustModel();

            $listData = $entrustModel->getCarList($params);

            if (isset($listData['error'])) {
                return $response->withJson(AppUnits::rtnMsg(201, $listData['error']));
            }

            $result = $entrustModel->writerExcel($listData);

            if (!$result['success']) {
                return $response->withJson(AppUnits::rtnMsg(201, $result['msg']));
            }

            return $response->withJson(AppUnits::rtnMsg(200, null, $result['data']));
        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201, $e->getMessage()));
        }
    }


}
