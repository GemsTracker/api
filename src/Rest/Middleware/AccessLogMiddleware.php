<?php


namespace Gems\Rest\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rest\Repository\AccesslogRepository;

class AccessLogMiddleware implements MiddlewareInterface
{
    public function __construct(AccesslogRepository $accesslogRepository)
    {
        $this->accesslogRepository = $accesslogRepository;

    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = $request->getMethod();
        $action = $this->getAction($request);

        $ip = $this->getIp($request);
        $message = null;

        $changed = $this->getChanged($method);

        $data = $this->getData($method, $request);

        $userId = $request->getAttribute('user_id');

        $this->accesslogRepository->logAction($action, $method, $changed, $message, $data, $ip, $userId);
    }

    /**
     * Get the current Action from the route
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getAction(ServerRequestInterface $request)
    {
        $routeResult = $request->getAttribute('Zend\Expressive\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        return str_replace(['.get', '.fixed'], '', $route->getName());
    }

    /**
     * Check if method causes a change
     *
     * @param $method string
     * @return bool
     */
    protected function getChanged($method)
    {
        switch($method) {
            case 'DELETE':
            case 'PATCH':
            case 'POST':
                return true;

        }
        return false;
    }

    /**
     * Get the data of the request
     *
     * @param $method string request method
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getData($method, ServerRequestInterface $request)
    {
        switch($method) {
            case 'GET':
            case 'DELETE':
                $data = $request->getAttributes();
                break;
            case 'PATCH':
            case 'POST':
                $data = $request->getBody()->getContents();
                break;
        }

        return $data;
    }

    /**
     * Get the user IP address
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    protected function getIp(ServerRequestInterface $request)
    {
        $params = $request->getServerParams();
        if (isset($params['REMOTE_ADDR'])) {
            return $params['REMOTE_ADDR'];
        }
        return null;
    }
}