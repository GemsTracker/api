<?php

namespace Gems\Rest\Action;

use Interop\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Expressive\Helper\UrlHelper;

class RestControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $loader = $container->get('loader');
        $db1 = $container->get('LegacyDb');

        $urlHelper = $container->get(UrlHelper::class);

        return new $requestedName($loader, $urlHelper, $db1);
    }
}