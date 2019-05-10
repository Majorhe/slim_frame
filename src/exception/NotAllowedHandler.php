<?php

namespace exception;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\NotAllowed;
use units\AppUnits;

class NotAllowedHandler extends NotAllowed
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        return $response->withStatus(405)->withJson(
            AppUnits::rtnMsg(405,'HTTP请求方法错误，请求方法应为: ' . implode(', ', $methods))
        );
    }
}