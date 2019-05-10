<?php

/**
 * 钩子系统
 * 可放在路由中间件 或者 应用中间件
 * @version 0.0.1
 */

namespace middleware;

use hooks\HookInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use units\AppUnits;

final class Hook
{
    const ROUTER_BEFORE_EXECUTE = 'before_router_execute';
    const ROUTER_AFTER_EXECUTE  = 'after_router_execute';
    const APP_BEFORE_EXECUTE    = 'before_app_execute';
    const APP_AFTER_EXECUTE     = 'after_app_execute';

    private static $_hooks = null;
    private $request;
    private $response;

    public function __construct($hookPos = null,HookInterface $hook = null)
    {
        if (self::$_hooks == null){
            self::initHooks();
        }

        if(!empty($hook)){
            self::addHook($hookPos,$hook);
        }

    }

    private static function initHooks()
    {
        self::$_hooks[self::ROUTER_BEFORE_EXECUTE] = new \SplQueue();
        self::$_hooks[self::APP_BEFORE_EXECUTE]    = new \SplQueue();
        self::$_hooks[self::ROUTER_AFTER_EXECUTE]  = new \SplQueue();
        self::$_hooks[self::APP_AFTER_EXECUTE]     = new \SplQueue();
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        $this->request  = $request;
        $this->response = $response;

        if ($next instanceof \Slim\Route) {

            $beforeTrigger = self::ROUTER_BEFORE_EXECUTE;
            $afterTrigger  = self::ROUTER_AFTER_EXECUTE;

        }else {

            $beforeTrigger = self::APP_BEFORE_EXECUTE;
            $afterTrigger  = self::APP_AFTER_EXECUTE;
        }

        try {

            $this->trigger($beforeTrigger);
            $response = $next($request, $response);
            $this->trigger($afterTrigger);
            return $response;

        } catch (\Exception $e) {
            return $response->withJson(AppUnits::rtnMsg(201,$e->getMessage()));
        }
    }

    /**
     * 执行钩子
     * @param $hookPos
     */
    private function trigger($hookPos)
    {
        while (count(self::$_hooks[$hookPos])) {
            call_user_func(self::$_hooks[$hookPos]->dequeue(),$this->request,$this->response);
        }
    }

    /**
     * 添加钩子
     * @param $hookPos
     * @param HookInterface $hook
     */
    public static function addHook($hookPos, HookInterface $hook)
    {
        if (self::$_hooks == null){
            self::initHooks();
        }

        if (isset(self::$_hooks[$hookPos])){
            self::$_hooks[$hookPos]->enqueue($hook);
        }
    }
}