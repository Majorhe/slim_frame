<?php

namespace units;

use models\SystemModel;
use Slim\Http\Request;

class AppUnits
{
    public static $app ;

    public static function setApp($app)
    {
        self::$app = $app;
    }

    public static function getApp()
    {
        return self::$app;
    }

    public static function getContainer()
    {
        return self::getApp()->getContainer();
    }

    public static function getLogger()
    {
        return self::getContainer()->get('logger');
    }

    public static function getParams(string $key = null)
    {
        $params = self::getContainer()->get('params');

        if(empty($key)){
            return $params;
        }

        return isset($params[$key]) ? $params[$key] : $params;
    }

    public static function debug($msg = null)
    {
        if(self::getParams('debug')){
            self::getLogger()->debug(is_array($msg) ? json_encode($msg,JSON_UNESCAPED_UNICODE) : $msg);
        }
    }

    public static function encryptWithOpenssl(array $data = [])
    {
        if(empty($data)) {
            return null;
        }

        return base64_encode(
            openssl_encrypt(
                json_encode($data,JSON_UNESCAPED_UNICODE),
                'AES-256-CBC',
                self::getParams('AES_KEY'),
                OPENSSL_RAW_DATA,
                self::getParams('AES_IV')
            )
        );
    }

    public static function decryptWithOpenssl($data = null)
    {
        if(empty($data)){
            return null;
        }

        return json_decode(
            openssl_decrypt(
                base64_decode($data),
                'AES-256-CBC',
                self::getParams('AES_KEY'),
                OPENSSL_RAW_DATA,
                self::getParams('AES_IV')
            ),true
        );
    }

    /**
     * 管理后台获取请求Java接口的Token
     * @return mixed
     */
    public static function getToken()
    {
        return self::getSaasUserInfo('token');
    }

    /**
     * 获取Saas平台的登录用户信息
     * @param null $key
     * @return mixed
     */
    public static function getSaasUserInfo($key = null)
    {
        $request  = self::getContainer()->get('request');
//        $saasUser = json_decode(base64_decode($request->getCookieParam(self::getParams('cookieName'))),true);

        $saasUser = json_decode(base64_decode('eyJiYXRjaE5vIjoiYmF0Y2g1YmM0NWY0ZTNiMjYxIiwidG9rZW4iOiJENzI0NTQ5Mzg3QkZCOUZDM0UxOEY3RTk5QzkzQzNCQiIsInVzZXJJZCI6IjEiLCJ1c2VyTmFtZSI6ImFkbWluMTIzIiwiaWQiOiIxIiwicGFzc3dvcmQiOiIwMTkyMDIzYTdiYmQ3MzI1MDUxNmYwNjlkZjE4YjUwMCIsImNvbXBhbnkiOnsiYWRkTWFuYWdlciI6IjEiLCJhZGRUaW1lIjoxNTAyMzgwODAwMDAwLCJiYWNrR3JvdW5kIjoiIiwiaWQiOiIxIiwibG9naW5VcmwiOiJodHRwOlwvXC90ZXN0LnNhYXMuYmFpY2FpcWljaGUuY29tXC9ETlciLCJsb2dvUGF0aCI6IiIsIm5hbWUiOiLploPploPph5HmnI0iLCJuYW1lU3ViIjoic2hhbnNoYW5jYXIiLCJzdGF0dXMiOiIxIiwidGl0bGVTdHlsZSI6ImNvbG9yQmxhY2siLCJ1cGRhdGVNYW5hZ2VyIjoiMSIsInVwZGF0ZU5hbWVTdWJUaW1lcyI6NDAsInVwZGF0ZVRpbWUiOjE1MDI3MjY0MDAwMDB9fQ=='),true);

        if(!empty($key) && isset($saasUser[$key])){
            return $saasUser[$key];
        }

        return $saasUser;
    }

    /**
     * 清楚cookie数据
     * @return bool
     */
    public static function logout()
    {
        return setcookie(self::getParams('cookieName'),null,0,self::getParams('cookieDomain'));
    }

    /**
     * 清除Redis的缓存
     * @param null $key
     * @return bool|int
     */
    public static function clearRedisCache($key = null)
    {

        if(!self::getParams('redis_cache')){
            return false;
        }

        $cacheKey = self::getSaasUserInfo('id');

        if(empty($cacheKey)){
            return false;
        }

        $redis = new RedisUnits();

        if(!empty($key)){
            return $redis->delCache($cacheKey,$key);
        }

        return $redis->delAllCache($cacheKey);
    }

    public static function getAuthMenuList()
    {

        $menuList = self::getMenuListByCache();

        $allowAction = [];

        if (!empty($menuList)) {

            foreach ($menuList as $obj) {
                $allowAction[$obj['url']] = $obj;
            }
        }

        return $allowAction;

    }

    public static function getLeftMenuList()
    {

        $menuList = self::getMenuListByCache();

        $menu = [] ;

        if (!empty($menuList)) {

            foreach ($menuList as $obj) {

                if(intval($obj['isMenu']) == 1){
                    $menu[] = $obj['url'];
                }
            }
        }

        return $menu;
    }

    public static function getMenuListByCache()
    {
        if(!self::getParams('redis_cache')){
            return self::getMenuByApi();
        }

        $redis = new RedisUnits();

        if(!($menuList = $redis->getCache(self::getSaasUserInfo('id'),'MenuList'))){

            $menuList = self::getMenuByApi();
            if(!empty($menuList)){
                $redis->setCache(self::getSaasUserInfo('id'),'MenuList',$menuList);
            }
        }

        return $menuList;

    }

    public static function getMenuByApi()
    {
        $menuList = null;

        $apiData = (new SystemModel())->queryCurrentUserMenu([
            'sysId'  => self::getParams('sysId'),
            'userId' => self::getSaasUserInfo('id')
        ]);

        if (isset($apiData['arrays']) && ! empty($apiData['arrays'])) {
            $menuList = $apiData['arrays'];
        }

        return $menuList;
    }

    public static function rtnMsg($code = 200 , $msg = null , $data = [] , $isEncrype = true)
    {
        $return = ['code' => $code , 'msg' => $msg , 'data' => $data];

        return $isEncrype ? [ 'content' => self::encryptWithOpenssl($return) ] : $return;
    }

    public static function paramsFilter(array $params)
    {
        $filter = function (array $params) use (&$filter) {
            foreach ($params as $key => $value) {
                if (is_string($value)) {
                    $params[$key] = trim(strip_tags($value));
                } else if (is_array($value)) {
                    $params[$key] = $filter($value);
                }
            }
            return $params;
        };
        return $filter($params);
    }


    /**
     * 车牌号正则校验
     *
     * @param $carNo
     * @return bool
     */
    public static function carNoValidator($carNo)
    {
        if (empty($carNo)) {
            return false;
        }

        if (mb_strlen($carNo, 'UTF8') > 8 || mb_strlen($carNo, 'UTF8') < 7) {
            return false;
        }
        /**
         * 判断标准
         * 1，第一位为汉字省份缩写
         * 2，第二位为大写字母城市编码
         * 3，后面是5位仅含字母和数字的组合
         */
        // 匹配普通车牌
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5}$/u";
        preg_match($regular, $carNo, $match);
        if (isset($match[0])) {
            return true;
        }

        // 匹配新能源车辆6位车牌
        // 小型新能源车
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[DF]{1}[0-9a-zA-Z]{5}$/u";
        preg_match($regular, $carNo, $match);
        if (isset($match[0])) {
            return true;
        }

        // 大型新能源车
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[0-9a-zA-Z]{5}[DF]{1}$/u";
        preg_match($regular, $carNo, $match);
        if (isset($match[0])) {
            return true;
        }

        return false;
    }

    /**
     * 车架号正则校验
     *
     * @param $carFrame
     * @return bool
     */
    public static function carFrameValidator($carFrame)
    {
        if (empty($carFrame)) {
            return false;
        }

        if (strlen($carFrame) !== 17) {
            return false;
        }

        $regular = "/[a-zA-Z0-9]{17}$/u";
        preg_match($regular, $carFrame, $match);
        if (isset($match[0])) {
            return true;
        }
        return false;
    }

    /**
     * 生产随机数
     *
     * @param int $len
     * @return string
     */
    public static function rand_str($len = 4)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );

        $charsLen = count($chars) - 1;
        shuffle($chars);                            //打乱数组顺序
        $str = '';
        for($i=0; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
        }
        return $str;
    }


    /**
     * 将车辆状态转换为悬赏状态和委案状态
     *
     * 车辆状态：1--已委托， 2--申请中，3--执行中，4--已撤销，5--已完成，6--委托失败
     * 悬赏状态：8--待分析， 0---待发布， 1---已发布， 2---申请中， 3----已授权，  4---可执行， 7---待确认， 9---待付款， 10---待回款， 5---已完成， 6---已失效， 11---已委托
     * 委案状态：1--为已委托， 2--为委托失败， 3--为已激活， 4--为激活失败
     *
     * @param $carStatus
     * @return array
     */
    public static function convertCarStatus2rewardStatus($carStatus)
    {
        if (empty($carStatus)) {
            return [];
        }

        $status = explode(',', $carStatus);

        $rewardStatus = $entrustStatus = [];

        foreach ($status as $state) {
            switch (intval($state)) {
                case 1:
                    array_push($rewardStatus, 11, 8, 0, 1);
                    array_push($entrustStatus, 1, 3);
                    break;
                case 2:
                    $rewardStatus[] = 2;
                    break;
                case 3:
                    array_push($rewardStatus, 3, 4, 7, 9, 10);
                    break;
                case 4:
                    $rewardStatus[] = 6;
                    break;
                case 5:
                    $rewardStatus[] = 5;
                    break;
                case 6:
                    array_push($entrustStatus, 2,4);
                    break;
                default:
                    break;
            }
        }

        return ['rewardStatus' => implode(',', $rewardStatus), 'entrustStatus' => implode(',', $entrustStatus)];
    }


    /**
     * 将悬赏状态和委案状态转换为车辆状态
     *
     * @param $rewardStatus
     * @param $entrustStatus
     * @return int
     */
    public static function convertRewardStatus2carStatus($rewardStatus, $entrustStatus)
    {
        if ((empty($rewardStatus) && intval($rewardStatus) !== 0) || empty($entrustStatus)) {
            return 0;
        }

        // 已委托
        if ($entrustStatus == 1 || $entrustStatus == 3) {
            // 已委托
            if (in_array($rewardStatus, [11, 8, 0, 1])) {
                return 1;
            }
            // 申请中
            if ($rewardStatus == 2) {
                return 2;
            }
            // 执行中
            if (in_array($rewardStatus, [3, 4, 7, 9, 10])) {
                return 3;
            }
            // 已撤销
            if ($rewardStatus == 6) {
                return 4;
            }
            // 已完成
            if ($rewardStatus == 5) {
                return 5;
            }
        }

        // 委托失败
        if ($entrustStatus == 2 || $entrustStatus == 4) {
            return 6;
        }
    }
}
