<?php

namespace exception;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\NotFound;
use units\AppUnits;

class NotFoundHandler extends NotFound
{

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withStatus(404)->withJson(AppUnits::rtnMsg(404,'Api not found'));
    }
}