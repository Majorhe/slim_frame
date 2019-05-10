<?php

namespace exception;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\Error;
use units\AppUnits;

class ErrorHandler extends Error
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception)
    {
        AppUnits::getLogger()->error($this->renderThrowableAsText($exception));

        return $response->withStatus(500)->withJson(AppUnits::rtnMsg(500,$exception->getMessage()));
    }

}