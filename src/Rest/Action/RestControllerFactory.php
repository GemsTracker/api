<?php

namespace Gems\Rest\Action;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class RestControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $loader = $container->get('loader');

        $urlHelper = $container->get(UrlHelper::class);

        return new $requestedName($container, $loader, $urlHelper);
    }
}