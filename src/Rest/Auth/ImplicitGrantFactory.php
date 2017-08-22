<?php


namespace Gems\Rest\Auth;

use League\OAuth2\Server\Grant\ImplicitGrant;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImplicitGrantFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        $valid = new \DateInterval('PT10M');
        if (!isset($config['implicit'], $config['implicit']['code_valid'])) {
            $valid = new \DateInterval($config['implicit']['code_valid']);
        }

        $authCodeGrant = new ImplicitGrant($valid);

        return $authCodeGrant;
    }
}