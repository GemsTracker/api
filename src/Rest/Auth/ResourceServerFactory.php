<?php


namespace Gems\Rest\Auth;

use Interop\Container\ContainerInterface;
use League\OAuth2\Server\ResourceServer;
use Zend\ServiceManager\Factory\FactoryInterface;


class ResourceServerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        return new ResourceServer(
            $container->get('Rest\Auth\AccessTokenRepository'), $config['certificates']['public']
        );
    }
}