<?php

namespace controller\admin;

use models\SystemModel;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use units\AppUnits;
use units\RedisUnits;

class HomeController
{
    public $container ;

    public function __construct (ContainerInterface $ci)
    {
        $this->container = $ci;
    }

    public function getMenuList(Request $request , Response $response , $args)
    {
        $data = [];

        //获取用户信息
        $data['userInfo'] = AppUnits::getSaasUserInfo();

        //获取菜单信息
        $data['menuList'] = array_keys(AppUnits::getAuthMenuList());

        // saas登录页面
        $data['saasHomeUrl'] = AppUnits::getParams('saasHomeUrl');

        return $response->withJson(AppUnits::rtnMsg(200,null,$data));
    }

    /**
     * 获取AccessToken
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function getAccessToken(Request $request , Response $response , $args)
    {
        $token = AppUnits::getToken();

        $data['AccessToken'] = AppUnits::encryptWithOpenssl(['token' => $token, 'expireTime' => time() + AppUnits::getParams('tokenExpireTime')]);

        return $response->withJson(AppUnits::rtnMsg(200,null,$data));
    }
}