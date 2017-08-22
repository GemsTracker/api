<?php

namespace Gems\Rest\Action;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zalt\Loader\ProjectOverloaderService;

class TempLoaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $loader = $container->get('loader');

        return new $requestedName($container, $loader);
    }
}