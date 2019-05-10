<?php

namespace middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use units\AppUnits;

class AdminAuth
{

    public function __construct ()
    {

    }

    public function __invoke (Request $request , Response $response , Callable $next)
    {
        if(!($request->getAttribute('route'))){
            return $response->withJson(AppUnits::rtnMsg(201,'访问地址不存在或是访问方式错误'));
        }

        /**
         * 判断是否登录 从cookie中获取用户登录信息
         */

        if(empty(AppUnits::getSaasUserInfo())){
            return $response->withJson(AppUnits::rtnMsg(202,'token过期', ['loginUrl' => AppUnits::getParams('saasLoginUrl')]));
        }

        $name = $request->getAttribute('route')->getName();

        if(in_array($name,AppUnits::getParams('adminWhiteList'))){
            return $next($request,$response);
        }

        /**
         * 验证请求头部AccessToken
         */

        if(!($token = $request->getHeader('AccessToken'))){
            return $response->withJson(AppUnits::rtnMsg(202,'token无效', ['loginUrl' => AppUnits::getParams('saasLoginUrl')]));
        }

        $token = AppUnits::decryptWithOpenssl($token[0]);

        if( ! isset($token['expireTime']) || ($token['expireTime'] < time())){
            return $response->withJson(AppUnits::rtnMsg(202,'token过期', ['loginUrl' => AppUnits::getParams('saasLoginUrl')]));
        }


        /**
         * 获取用户的权限菜单 再比对当前 $name 判断是否有权限
         */

        $rules = AppUnits::getAuthMenuList();

        if (!array_key_exists($name, $rules) ) {
            return $response->withJson(AppUnits::rtnMsg(201,'很抱歉您没有权限进行此操作，如有疑问请联系管理员'));
        }

        $level    = $rules[$name]['level'];
        $menuType = $rules[$name]['menuType'];

        if ($level < $menuType){
            //权限不足
            return $response->withJson(AppUnits::rtnMsg(201,'很抱歉您没有权限进行此操作，如有疑问请联系管理员'));
        }

        return $next($request,$response);
    }
}