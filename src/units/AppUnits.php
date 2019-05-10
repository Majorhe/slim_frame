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


    public function carNoValidator($carNo)
    {
        if (empty($carNo)) {
            return false;
        }

        if (strlen($carNo) > 8 || strlen($carNo) < 7) {
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

}
