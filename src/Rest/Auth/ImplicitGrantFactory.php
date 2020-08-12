<?php


namespace Gems\Rest\Auth;

use Interop\Container\ContainerInterface;
use League\OAuth2\Server\Grant\ImplicitGrant;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ImplicitGrantFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        $valid = new \DateInterval('PT10M');
        if (isset(
            $config['oauth2'],
            $config['oauth2']['grants'],
            $config['oauth2']['grants']['implicit'],
            $config['oauth2']['grants']['implicit']['code_valid'])
        ) {
            $valid = new \DateInterval($config['oauth2']['grants']['implicit']['code_valid']);
        }

        $implicitGrant = new ImplicitGrant($valid);

        return $implicitGrant;
    }
}
