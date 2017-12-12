<?php


namespace Gems\Rest\Auth;


use Interop\Container\ContainerInterface;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AuthCodeGrantFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $authCodeRepository = $container->get(AuthCodeRepositoryInterface::class);
        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        $config = $container->get('config');


        $valid = new \DateInterval('PT10M');
        if (isset(

            $config['oauth2'],
            $config['oauth2']['grants'],
            $config['oauth2']['grants']['authorization_code'],
            $config['oauth2']['grants']['authorization_code']['code_valid'])
        ) {
            $valid = new \DateInterval($config['oauth2']['grants']['authorization_code']['code_valid']);
        }

        $authCodeGrant = new AuthCodeGrant($authCodeRepository, $refreshTokenRepository, $valid);

        return $authCodeGrant;
    }
}