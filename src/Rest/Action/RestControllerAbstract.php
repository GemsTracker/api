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

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = strtolower($request->getMethod());
        $path = $request->getUri()->getPath();

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