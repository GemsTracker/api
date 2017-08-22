<?php


namespace Gems\Rest\Auth;


use Interop\Container\ContainerInterface;
use League\OAuth2\Server\AuthorizationServer;
use Zend\ServiceManager\Factory\FactoryInterface;

class AuthorizationServerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        $accessTokenRepository = $container->get('Gems\Rest\Auth\AccessTokenRepository');
        $clientRepository = $container->get('Gems\Rest\Auth\ClientRepository');
        $scopeRepository = $container->get('Gems\Rest\Auth\ScopeRepository');

        $this->server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $config['certificates']['private'],
            $config['app_key']
        );

        if(isset($config['oauth2']['grants'])) {
            $this->addGrants($config['oauth2']['grants'], $container);
        }

        return $this->server;
    }

    protected function addGrants($grants, $container)
    {
        if (isset($grants['authorization_code'], $grants['authorization_code']['class'])) {

            if (isset($grants['authorization_code']['token_valid'])) {
                $valid = new \DateInterval($grants['authorization_code']['token_valid']);
            } else {
                $valid = new \DateInterval('PT1H');
            }

            // Enable the client credentials grant on the server
            $this->server->enableGrantType(
                $container->get($grants['authorization_code']['class']),
                $valid // access tokens will expire after 1 hour
            );
        }
        if (isset($grants['client_credentials'], $grants['client_credentials']['class'])) {

            if (isset($grants['client_credentials']['token_valid'])) {
                $valid = new \DateInterval($grants['client_credentials']['token_valid']);
            } else {
                $valid = new \DateInterval('PT1H');
            }

            // Enable the client credentials grant on the server
            $this->server->enableGrantType(
                $container->get($grants['client_credentials']['class']),
                $valid // access tokens will expire after 1 hour
            );
        }
        if (isset($grants['password'], $grants['password']['class'])) {

            if (isset($grants['password']['token_valid'])) {
                $valid = new \DateInterval($grants['password']['token_valid']);
            } else {
                $valid = new \DateInterval('PT1H');
            }

            // Enable the client credentials grant on the server
            $this->server->enableGrantType(
                $container->get($grants['password']['class']),
                $valid // access tokens will expire after 1 hour
            );
        }
        if (isset($grants['refresh_token'], $grants['refresh_token']['class'])) {

            if (isset($grants['refresh_token']['token_valid'])) {
                $valid = new \DateInterval($grants['refresh_token']['token_valid']);
            } else {
                $valid = new \DateInterval('PT1H');
            }

            // Enable the client credentials grant on the server
            $this->server->enableGrantType(
                $container->get($grants['refresh_token']['class']),
                $valid // access tokens will expire after 1 hour
            );
        }
    }
}