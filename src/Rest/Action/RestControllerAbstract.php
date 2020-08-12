<?php


namespace Gems\Rest\Action;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Zend_Db;


abstract class RestControllerAbstract implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string Current Method
     */
    protected $method;

    /**
     * @var array Current route options
     */
    protected $routeOptions;

    /**
     * @var string|null Current User ID
     */
    protected $userId;

    /**
     * @var string|null Current User login name
     */
    protected $userName;

    /**
     * @var string|null Current user base organization
     */
    protected $userOrganization;

    protected function getUserAtributesFromRequest(ServerRequestInterface $request)
    {
        $this->userId = $request->getAttribute('user_id');
        $this->userName = $request->getAttribute('user_name');
        $this->userOrganization = $request->getAttribute('user_organization');
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = strtolower($request->getMethod());
        $path = $request->getUri()->getPath();

        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        $this->routeOptions = $route->getOptions();

        if ($method != 'options'
            && isset($this->routeOptions['methods'])
            &&!in_array($request->getMethod(), $this->routeOptions['methods'])
        ) {
                return new EmptyResponse(405);
        }

        $this->getUserAtributesFromRequest($request);

        if (($method == 'get') && (substr($path, -10) === '/structure')) {
            if (method_exists($this, 'structure')) {
                return $this->structure($request, $delegate);
            }
        } elseif (method_exists($this, $method)) {
            $this->method = $method;


            return $this->$method($request, $delegate);
        }

        return new EmptyResponse(501);
    }
}
