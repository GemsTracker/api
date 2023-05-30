<?php

namespace Gems\Rest\Middleware;

use Gems\Rest\Request\MezzioRequestWrapper;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class LegacyRequestMiddleware implements MiddlewareInterface
{
    private $requestWrapper;

    public function __construct(MezzioRequestWrapper $mezzioRequestWrapper)
    {
        $this->requestWrapper = $mezzioRequestWrapper;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->requestWrapper->setRequest($request);
        return $delegate->process($request);
    }
}