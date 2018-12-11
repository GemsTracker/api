<?php

namespace Gems\Rest\Action;

use Gems\Rest\Repository\AccesslogRepository;
use Interop\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Expressive\Helper\UrlHelper;

class RestControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $accessTokenRepository = $container->get(AccesslogRepository::class);
        $loader = $container->get('loader');
        $db1 = $container->get('LegacyDb');

        $urlHelper = $container->get(UrlHelper::class);

        return new $requestedName($accessTokenRepository, $loader, $urlHelper, $db1);
    }
}