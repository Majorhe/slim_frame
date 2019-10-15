<?php


namespace controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Container;
use models\Admin as AdminModel;

class IndexController
{
    protected  $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function home(Request $request, Response $response, $args)
    {
        return $this->container->view->render($response, '/application/index.twig', [
            'title' => 'test nginx proxy!'
        ]);
    }

    public function test(Request $request, Response $response, $args)
    {
        try {
            var_dump($request->getParams());
            var_dump($args);
            var_dump($request->isGet());

            exit();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function testDb(Request $request, Response $response, $args)
    {
        try {
            $adminModel = new AdminModel();
            var_dump($adminModel->setConnection());
            $db = $this->container->db;
            var_dump();
            var_dump($db->table('admin')->first());
            var_dump($db->table('admin')->getConnection());
            exit();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
