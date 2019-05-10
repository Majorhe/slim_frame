<?php

namespace hooks;

use models\SystemModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use units\AppUnits;
use units\RedisUnits;

/**
 * 操作员管理日志
 * Class BackendLogHook
 * @package hooks
 */
class BackendLogHook implements HookInterface
{
    private $userType = 1;
    public static $beforeData = [];
    public static $afterData  = [];

    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        $token = AppUnits::getToken();

        if (empty($token)) {
            return false;
        }

        $redis = new RedisUnits();

        $uid = AppUnits::getSaasUserInfo('id');

        //首页刷新缓存
        if(AppUnits::getApp()->getContainer()->get('request')->getUri()->getPath() == '/'){
            $redis->delCache($uid,'operatorInfo');
        }

        if(!($operatorInfo = $redis->getCache($uid,'operatorInfo'))){

            $systemModel = new SystemModel();

            $operator = $systemModel->accessTokenToUserInfo(['token' => $token]);

            $operatorInfo = [];

            if (isset($operator['operator']) && !empty($operator['operator'])) {
                $operatorInfo = array_merge(isset($operator['operatorInfo']) ? $operator['operatorInfo'] : [] , $operator['operator']);
            }

            if(!empty($operatorInfo)){
                $redis->setCache($uid,'operatorInfo',$operatorInfo);
            }
        }

        if( ! ($request->getAttribute('route')) ||
            ! ($name = $request->getAttribute('route')->getName()))
        {
            return false;
        }

        $s_menu = AppUnits::getAuthMenuList();

        if (!is_array($s_menu) || empty($s_menu)){
            return false;
        }

        if(!isset($s_menu[$name])){
            return false;
        }

        $logData = [

            'router' => [
                "routerId" => isset($s_menu[$name]['id']) ? $s_menu[$name]['id'] : null,
                "url"      => isset($s_menu[$name]['url']) ? $s_menu[$name]['url'] : null,
                "remark"   => isset($s_menu[$name]['remark']) ? $s_menu[$name]['remark'] : null
            ],

            'user' => [
                "userId" => $uid,
                "name"   => isset($operatorInfo['name']) ? $operatorInfo['name'] : '',
                "mobile" => isset($operatorInfo['mobile']) ? $operatorInfo['mobile'] : ''
            ],

            'userType'    => $this->userType,
            'description' => isset($s_menu[$name]['remark']) ? $s_menu[$name]['remark'] : null,
        ];

        if (!empty(self::$afterData) || !empty(self::$beforeData)){

            ksort(self::$beforeData);
            ksort(self::$afterData);

            $logData['data'] = [
                'before' => json_encode(self::$beforeData, JSON_UNESCAPED_UNICODE),
                'after'  => json_encode(self::$afterData, JSON_UNESCAPED_UNICODE)
            ];

        }

        return (new SystemModel())->addOperateLog($logData);
    }
}