<?php


namespace Gems\Rest\Action;

use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Interop\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Expressive\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend_Db;


abstract class RestControllerAbstract implements ServerMiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $db;

    /**
     *
     * @var ProjectOverloader
     */
    protected $loader;

    protected $method;


    /**
     *
     * RestControllerAbstract constructor.
     * @param ProjectOverloader $loader
     * @param UrlHelper $urlHelper
     * @param $LegacyDb Init Zend DB so it's loaded at least once, needed to set default Zend_Db_Adapter for Zend_Db_Table
     */
    public function __construct(ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {

        $this->loader = $loader;
        //$this->loader->verbose = true;
        $this->loader->legacyClasses = true;

        $this->helper = $urlHelper;
    }

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