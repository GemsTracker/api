<?php


namespace Gems\Rest\Auth;

use Interop\Container\ContainerInterface;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;
use Zend\ServiceManager\Factory\FactoryInterface;


class ResourceServerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        $certificates        = $config['certificates'];
        $passPhrase          = array_key_exists('passPhrase', $certificates) ? $certificates['passPhrase'] : null;
        $keyPermissionsCheck = array_key_exists('keyPermissionsCheck', $certificates) ? $certificates['keyPermissionsCheck'] : true;

        $pubKey = new CryptKey($certificates['public'], $passPhrase, $keyPermissionsCheck);

        return new ResourceServer(
            $container->get('Gems\Rest\Auth\AccessTokenRepository'), $pubKey
        );
    }
}