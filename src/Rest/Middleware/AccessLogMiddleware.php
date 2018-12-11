<?php


namespace Gems\Rest\Middleware;


use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessLogMiddleware implements MiddlewareInterface
{
    /**
     * @var AccesslogRepository
     */
    protected $accesslogRepository;

    public function __construct(AccesslogRepository $accesslogRepository)
    {
        $this->accesslogRepository = $accesslogRepository;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($result = $this->accesslogRepository->logAction($request)) {
            $request = $request->withAttribute($this->accesslogRepository->requestAttributeName, $result);
        }
        return $delegate->process($request);
    }
}