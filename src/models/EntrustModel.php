<?php

namespace models;


use units\AppUnits;

/**
 * Class EntrustModel
 * @package models
 *
 * @method getEntrustList(array $params) { v1.0.1 获取委案列表 }
 *
 * @method getHistoryList(array $params) { v1.0.1 获取上传委案历史列表 }
 *
 * @method getEntrustInfo(array $params) { v1.0.1 获取委案详情 }
 *
 * @method checkPermission(array $params) { v1.0.1 校验是否有操作资方的权限 }
 *
 * @method getCarList(array $params) { v1.0.1 获取同一批次的悬赏车辆列表 }
 *
 * @method batchImport(array $params) { v1.0.1 批量导入委案 }
 *
 */
class EntrustModel extends BaseModel
{

    public $method2Module = [
        'getEntrustList'  => 'car-service/carEntrust/list',
        'getEntrustInfo'  => 'car-service/carEntrust/info',
        'checkPermission' => 'car-service/fundProvider/havePermission',
        'getHistoryList'  => 'car-service/carEntrustBatch/list',
        'getCarList'      => 'car-service/carEntrustBatch/carList',
        'batchImport'     => 'car-service/carEntrust/batchImport'
    ];

    public $entrustFileTitle = [
        'A' => ['fieldName' => 'carFundProvider', 'title' => '资方名称'],
        'B' => ['fieldName' => 'carNo', 'title' => '车牌号'],
        'C' => ['fieldName' => 'carFrameNo', 'title' => '车架号'],
        'D' => ['fieldName' => 'expireDays', 'title' => '逾期天数'],
        'E' => ['fieldName' => 'entrustPrice', 'title' => '委托金额'],
        'F' => ['fieldName' => 'carOwnerName', 'title' => '车主姓名'],
        'G' => ['fieldName' => 'carOwnerIdno', 'title' => '车主身份证号'],
        'H' => ['fieldName' => 'carOwnerPhone', 'title' => '车主手机号'],
        'I' => ['fieldName' => 'carBrand', 'title' => '车型'],
        'J' => ['fieldName' => 'carColor', 'title' => '颜色'],
        'K' => ['fieldName' => 'gpsStatus', 'title' => 'GPS状态'],
        'L' => ['fieldName' => 'gpsTypes', 'title' => 'GPS登录方式'],
        'M' => ['fieldName' => 'gpsAccount', 'title' => 'GPS登录账号'],
        'N' => ['fieldName' => 'gpsPasswd', 'title' => 'GPS登录密码'],
        'O' => ['fieldName' => 'deliveryPlace', 'title' => '交车地点'],
        'P' => ['fieldName' => 'remark', 'title' => '备注'],
        'Q' => ['fieldName' => 'reason', 'title' => '失败原因']
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setEncryptMethod('aes');
    }

    /**
     * 保存上传的委案文件
     *
     * @param $file
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function uploadFile($file)
    {
        $mimeType = $file->getClientMediaType();
        if (!in_array($mimeType, array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'))) {
            return ['success' => false, 'msg' => '文件类型错误'];
        }

        // 设置上传的文件保存路径
        $filePath = ROOT . 'public/temp_file/';
        if (!is_dir($filePath)) {
            mkdir($filePath, 0766);
        }

        if (!is_writable($filePath)) {
            chmod($filePath, 0766);
        }

        $filename = $file->getClientFileName();

        // 获取文件名与文件后缀名
        $explodeFilename = explode('.', $filename);

        // 设置新的文件名并保存文件
        $newFilename = \date('YmdHis') . uniqid('-') . '.' . $explodeFilename[count($explodeFilename) - 1];
        $newFilePath = $filePath . $newFilename;
        $file->moveTo($newFilePath);

        // 校验委案文件模版是否正确
        $excelReader = \PHPExcel_IOFactory::createReaderForFile($newFilePath);
        $excelObj = $excelReader->load($newFilePath);
        $sheet = $excelObj->getSheet(0);
        $cols = $sheet->getHighestColumn();
        $rows = $sheet->getHighestRow();

        if ($cols != 'P') {
            return ['success' => false, 'msg' => '导入失败！文件未匹配成功，您可使用系统提供的模版导入！'];
        }

        if ($rows < 2) {
            return ['success' => false, 'msg' => '导入失败！导入的委案信息不能为空！'];
        }

        // 导入失败！单次仅能上传一个资方的数据！
        $fundProvider = $sheet->getCell('A2')->getValue();
        for ($i = 3; $i <= $rows; $i++) {
            if ($fundProvider != $sheet->getCell('A' . $i)->getValue()) {
                return ['success' => false, 'msg' => '导入失败！单次仅能上传一个资方的数据！'];
            }
        }

        // 校验用户是否有操作资方的权限
        $result = $this->checkPermission(['fundName' => $fundProvider]);

        if (isset($result['error'])) {
            return ['success' => false, 'msg' => $result['error']];
        }

        if (!$result['havePermission']) {
            return ['success' => false, 'msg' => '导入失败！您没有权限上传' . $fundProvider . '资方委案！'];
        }

        // 校验资方是否有激活的合同


        return ['success' => true, 'data' => ['filename' => $newFilename]];
    }


    /**
     * 校验委案数据
     *
     * @param $filename
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function checkEntrustData($filename)
    {
        // 使用phpexcel获取excel文件信息
        $filePath = ROOT . 'public/temp_file/' . $filename;
        $excelReader = \PHPExcel_IOFactory::createReaderForFile($filePath);
        $excelObj = $excelReader->load($filePath);
        $sheet = $excelObj->getSheet(0);
        $rows = $sheet->getHighestRow();
        $cols = $sheet->getHighestColumn();

        $validData = $invalidData = [];
        for ($i = 2; $i <= $rows; $i++) {
            $temp = [];
            $msg = [];
            $flag = false;
            for ($j = 'A'; $j != $cols; $j++) {
                $temp[$this->entrustFileTitle[$j]['fieldName']] = trim($sheet->getCell($j . $i)->getValue());
                switch ($j) {
                    case 'A':
                        // 资方判断
                        if (empty($temp['carFundProvider'])) {
                            $flag = true;
                            $msg[] = '资方名称不能为空';
                        }
                        break;
                    case 'B':
                        // 车牌号校验
                        if (empty($temp['carNo'])) {
                            $flag = true;
                            $msg[] = '车牌号不能为空';
                            break;
                        }
                        if (!AppUnits::carNoValidator($temp['carNo'])) {
                            $flag = true;
                            $msg[] = '车牌号不合法';
                            break;
                        }
                        break;
                    case 'C':
                        // 车架号校验
                        if (empty($temp['carFrameNo'])) {
                            $flag = true;
                            $msg[] = '车架号不能为空';
                            break;
                        }
                        if (!AppUnits::carFrameValidator($temp['carFrameNo'])) {
                            $flag = true;
                            $msg[] = '车架号不合法';
                            break;
                        }
                        break;
                    case 'D':
                        // 逾期天数校验
                        if (empty($temp['expireDays'])) {
                            $flag = true;
                            $msg[] = '逾期天数不能为空';
                            break;
                        }
                        if (!is_numeric($temp['expireDays']) || intval($temp['expireDays']) < 0 || intval($temp['expireDays']) > 9999) {
                            $flag = true;
                            $msg[] = '逾期天数必须是数字且不能超过9999天';
                            break;
                        }
                        break;
                    case 'E':
                        // 委托金额检验
                        if (empty($temp['entrustPrice'])) {
                            $flag = true;
                            $msg[] = '委托金额不能为空';
                            break;
                        }
                        if (!is_numeric($temp['entrustPrice']) || floatval($temp['entrustPrice']) < 0) {
                            $flag = true;
                            $msg[] = '委托金额必须是数字且不能小于0元';
                            break;
                        }
                        break;
                    default:
                        break;
                }
            }

            if ($flag) {
                $temp['reason'] = implode(',', $msg);
                $invalidData[] = $temp;
            } else {
                $validData[] = $temp;
            }
        }

        return ['success' => true, 'data' => ['validData' => $validData, 'invalidData' => $invalidData]];
    }


    /**
     * 写入委案数据到excel
     *
     * @param array $listData
     * @param null $filename
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function writerExcel(array $listData, $filename = null)
    {
        if (empty($listData)) {
            return ['success' => false, 'msg' => '数据不能为空'];
        }

        $objPHPExcel = new \PHPExcel();

        // 设置sheet名称
        $objPHPExcel->setActiveSheetIndex(0)->setTitle('上传委案历史');

        // 设置表头名称
        foreach ($this->entrustFileTitle as $col => $titleData) {
            $objPHPExcel->getActiveSheet()->setCellValue($col . '1', $titleData['title']);
        }

        $index = 1;
        $cols = array_keys($this->entrustFileTitle);
        foreach ($listData as $data) {
            $index++;

            foreach ($cols as $col) {
                $objPHPExcel->getActiveSheet()->setCellValue($col . $index, $data[$this->entrustFileTitle[$col]['fieldName']]);
            }

//            $objPHPExcel->getActiveSheet()->setCellValue('A' . $index, $data['carFundProvider']);
//            $objPHPExcel->getActiveSheet()->setCellValue('B' . $index, $data['carNo']);
//            $objPHPExcel->getActiveSheet()->setCellValue('C' . $index, $data['carFrameNo']);
//            $objPHPExcel->getActiveSheet()->setCellValue('D' . $index, $data['expireDays']);
//            $objPHPExcel->getActiveSheet()->setCellValue('E' . $index, number_format($data['entrustPrice'], 2, '.', ','));
//            $objPHPExcel->getActiveSheet()->setCellValue('F' . $index, $data['carOwnerName']);
//            $objPHPExcel->getActiveSheet()->setCellValue('G' . $index, $data['carOwnerIdno']);
//            $objPHPExcel->getActiveSheet()->setCellValue('H' . $index, $data['carOwnerPhone']);
//            $objPHPExcel->getActiveSheet()->setCellValue('I' . $index, $data['carBrand']);
//            $objPHPExcel->getActiveSheet()->setCellValue('J' . $index, $data['carColor']);
//            $objPHPExcel->getActiveSheet()->setCellValue('K' . $index, $data['gpsStatus']);
//            $objPHPExcel->getActiveSheet()->setCellValue('L' . $index, $data['gpsTypes']);
//            $objPHPExcel->getActiveSheet()->setCellValue('M' . $index, $data['gpsAccount']);
//            $objPHPExcel->getActiveSheet()->setCellValue('N' . $index, $data['gpsPasswd']);
//            $objPHPExcel->getActiveSheet()->setCellValue('O' . $index, $data['deliveryPlace']);
//            $objPHPExcel->getActiveSheet()->setCellValue('P' . $index, $data['remark']);
//            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $index, $data['reason']);
        }

        // 创建excel
        if (empty($filename)) {
            $filename =  \date('YmdHis') . uniqid('-') . '.xlsx';
        }

        $objWrite = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        // 设置上传的文件保存路径
        $_savePath = ROOT . 'public/temp_file/';
        if (!is_dir($_savePath)) {
            mkdir($_savePath, 0766);
        }

        if (!is_writable($_savePath)) {
            chmod($_savePath, 0766);
        }

        // 保存生成的excel文件
        $objWrite->save($_savePath . $filename);

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        return ['success' => true, 'data' => ['url' => $http_type . $_SERVER('HTTP_HOST') . '/temp_file/' . $filename]];
    }
}
