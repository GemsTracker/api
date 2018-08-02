<?php


namespace Gems\Rest\Action;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Diactoros\Response\EmptyResponse;
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

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = strtolower($request->getMethod());
        $path = $request->getUri()->getPath();

        $routeResult = $request->getAttribute('Zend\Expressive\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        $this->routeOptions = $route->getOptions();

        if ($method != 'options'
            && isset($this->routeOptions['methods'])
            &&!in_array($request->getMethod(), $this->routeOptions['methods'])
        ) {
                return new EmptyResponse(405);
        }

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