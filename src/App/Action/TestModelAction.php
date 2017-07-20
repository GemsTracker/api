<?php


namespace App\Action;

use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;

class TestModelAction implements ServerMiddlewareInterface
{
    /**
     *
     * @var ProjectOverloader
     */
    protected $loader;

    public function __construct(ContainerInterface $container, ProjectOverloader $loader)
    {
        $this->loader = $loader;
        //$this->loader->verbose = true;
        $this->loader->legacyClasses = true;

        $this->db = $container->get('db');
    }



    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $model = $this->loader->create('Gems_Model_OrganizationModel');
        $model->applyBrowseSettings();

        return new JsonResponse($model->load());
    }
}