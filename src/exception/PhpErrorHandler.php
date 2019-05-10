<?php

namespace exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\PhpError;
use units\AppUnits;

class PhpErrorHandler extends PhpError
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Throwable $error)
    {
        AppUnits::getLogger()->error("错误信息：" . $this->renderThrowableAsText($error));

        return $response->withStatus(500)->withJson(
            AppUnits::rtnMsg(500,$error->getMessage())
        );
    }
}