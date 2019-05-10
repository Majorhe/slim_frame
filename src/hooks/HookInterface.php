<?php

namespace hooks;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface HookInterface
{
    public function __invoke(RequestInterface $request , ResponseInterface $response);
}