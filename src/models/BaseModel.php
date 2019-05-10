<?php

namespace models;

use units\AppUnits;
use units\CurlUnits;

class BaseModel
{
    protected $method2Module = [];

    protected $_method = 'get';

    protected $limit_time = null;

    protected $encrypt = 'base';

    protected $javaApiUrlKey = 'javaApiNewUrl';

    public function __construct()
    {

    }

    public function execute($url,$params)
    {
        $curlUnits = new CurlUnits();

        if (!is_null($this->limit_time)) {
            $curlUnits->setCurlTimeLimit($this->limit_time);
        }

        $result = $curlUnits->getDataByCurl($url, $this->encode($params), $this->_method,true);

        if(isset($result['error'])){
            AppUnits::debug("CURL错误信息：" .  json_encode(['methodName' => $url, 'params' => $params, 'error' => $result['error']], JSON_UNESCAPED_UNICODE));
            return $result;
        }

        return $this->decode($result);
    }

    public function __call($methodName, $params)
    {
        if (!\array_key_exists($methodName, $this->method2Module)) {
            return false;
        }

        if (empty($params)){
            $params[] = [];
        }

        $curlUnits = new CurlUnits();

        if (!is_null($this->limit_time)) {
            $curlUnits->setCurlTimeLimit($this->limit_time);
        }

        $result = $curlUnits->getDataByCurl(
            $this->createRequestUrl($this->method2Module[$methodName]),
            $this->encode($params[0]),
            $this->_method,
            true
        );

        if(isset($result['error'])){
            AppUnits::debug("CURL错误信息：" .  json_encode(['methodName' => $methodName, 'params' => $params, 'error' => $result['error']], JSON_UNESCAPED_UNICODE));
            return $result;
        }

        return $this->decode($result);
    }

    protected function decode($reqStr)
    {
        if (empty($reqStr)) {
            return false;
        }

        AppUnits::debug("接口返回的数据：" . $reqStr);

        switch ($this->encrypt) {
            case 'base' :
                $res = json_decode(base64_decode($reqStr), true);
                break;

            case 'aes' :
                $res = json_decode(openssl_decrypt(hex2bin(base64_decode($reqStr)), 'AES-128-CBC', AppUnits::getParams('JAVA_AES_KEY'),OPENSSL_RAW_DATA, AppUnits::getParams('JAVA_AES_IV')), true);
                break;

            default :
                $res = json_decode(base64_decode($reqStr), true);
                break;
        }

        $resultCode = 1000;
        $resultMsg = null;
        if (isset($res['header']['resultMsg'])) {
            $resultCode = $res['header']['resultMsg'];
            $resultMsg = null;
        } elseif (isset($res['busiData']['resultCode'])) {
            $resultCode = $res['busiData']['resultCode'];
            $resultMsg = isset($res['busiData']['resultMsg']) ? $res['busiData']['resultMsg'] : null;
        }

        AppUnits::debug("接口返回的数据 busiData 数据：" . json_encode($res,JSON_UNESCAPED_UNICODE));

        if ($resultCode && $resultCode != '1000') {

            switch ((int) $resultCode) {
                case 1008:
                    AppUnits::logout();
                    return $this->error($resultCode, $resultMsg);

                case 1067:
                    return ['error' => $res['busiData']['errorMsgList']];

                default:
                    return $this->error($resultCode, $resultMsg);
            }
        }

        return isset($res['busiData']) ? $res['busiData'] : $res;
    }

    protected function encode(array $params)
    {

        /**
         * sign加密算法：
         * 拼接
         * orgCode，
         * batchNo ,
         * seq成字符串，并由MD5加密后的密文。
         * seqNo=2F02DBA074DE64A5D32A57227FE6337A，由服务端提供，生产环境会做更改。
         */

        $orgCode = $transNo = $batchNo = $seqNo = null;

        extract(AppUnits::getParams('javaApiParams'));

        $params = [

            'header' => [
                'orgCode'   => $orgCode,
                'transNo'   => $transNo,
                'transDate' => date('Y-m-d H:i:s', time()),
                'token'     => AppUnits::getToken()
            ],

            'busiData' => array_merge($params,['batchNo' => $batchNo]),

            'securityInfo' => [
                'sign' => md5($orgCode . $transNo . $seqNo)
            ]
        ];

        AppUnits::debug("请求接口的数据（加密前）：" . json_encode($params,JSON_UNESCAPED_UNICODE));

        switch ($this->encrypt) {
            case 'base' :
                return ['content' => base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE))];

            case 'aes' :
                return ['content' => base64_encode(bin2hex(openssl_encrypt(json_encode($params, JSON_UNESCAPED_UNICODE), 'AES-128-CBC', AppUnits::getParams('JAVA_AES_KEY'),OPENSSL_RAW_DATA, AppUnits::getParams('JAVA_AES_IV'))))];

            default :
                return ['content' => base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE))];
        }


    }

    protected function createRequestUrl($modules)
    {
        return trim(AppUnits::getParams($this->javaApiUrlKey), '/') . '/' . trim($modules, '/');
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }
    
    public function setPost(){
        $this->_method = 'post';
        return $this;
    }

    public function setCurlLimitTime($limitTime = null)
    {
        $this->limit_time = $limitTime;
        return $this;
    }


    public function setEncryptMethod($encrypt = 'base')
    {
        $encrypt = strtolower($encrypt);
        if (in_array($encrypt, ['base', 'aes'])) {
            $this->encrypt = $encrypt;
        }
        return $this;
    }

    protected function error($code, $msg = null){
        $error = [
            '1001' => '查询必填项为空',
            '1002' => '验签失败',
            '1003' => '网络超时',
            '1004' => '读取请求数据IO异常',
            '1005' => '用户名或者密码错误',
            '1006' => '账号已被禁用，请联系管理员',
            '1007' => '账号已被锁定，请联系管理员',
            '1008' => 'token失效',
            '1009' => '用户未登录',
            '1010' => '用户不存在',
            '1011' => 'json非法索引访问数组时异常',
            '1012' => 'json数组下标越界异常',
            '1013' => 'json参数类型错误',
            '1014' => '查询项不存在',
            '1015' => '用户名已存在',
            '1016' => '密码和确认密码不一致',
            '1017' => '该用户名已存在',
            '1019' => '该权限有用户在使用 无法删除',
            '1020' => '原始密码错误',
            '1032' => '地区名称格式错误',
            '1033' => '电话号码格式错误',
            '1034' => '数据已录入',
            '1035' => '数据分析中',
            '1036' => '验证码错误',
            '1037' => '您已上传该线索',
            '1038' => '短信验证码发送失败',
            '1039' => '积分不足，请获取积分',
            '1040' => '该车牌悬赏信息已存在',
            '1041' => '该悬赏信息已授权',
            '1042' => '审批越界',
            '1043' => '不能赠送自己积分',
            '1044' => '本批次没有订单需要查询',
            '1045' => '未找到相关线索',
            '1046' => '系统检测到当前版本过低，请前往应用商店升级',
            '1047' => '该分享已失效',
            '1048' => '车辆Vin码和发动机号不能为空',
            '1049' => '车辆违章正在查询中，请耐心等待查询结果',
            '1050' => '车辆保险正在查询中，请耐心等待查询结果',
            '1051' => '大区下面有签约团队，不能删除',
            '1052' => '负者省份已被分配',
            '1053' => '资方名称重复',
            '1054' => '所选车辆的当前状态不能被下架',
            '1055' => '该车已授权给其他人，请关闭其他订单后再授权',
            '1056' => '订单状态不能取消或者已被关闭',
            '1057' => '悬赏价格必须大于0',
            '1058' => '您已抢过该订单',
            '1059' => '您已取消该订单',
            '1060' => '每次最多分配100辆',
            '1061' => '该订单已失效',
            '1062' => '该订单已被处理',
            '1063' => '延期失败',
            '1064' => '该用户已存在签约申请',
            '1065' => '该用户已签约',
            '1066' => '该授权已经有延期申请',
            '1067' => '批量数据不合法',
            '1068' => '已申请的车辆不可修改资方',
            '1071' => '订单已授权'
        ];

        return [
            'error' => isset($error[$code]) ? $error[$code] : (is_null($msg) ? $this->responseCode : $msg),
        ];
    }

}