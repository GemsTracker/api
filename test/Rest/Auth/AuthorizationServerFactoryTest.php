<?php


namespace Gemstest\Rest\Auth;

use Gems\Rest\Auth\AccessTokenRepository;
use Gems\Rest\Auth\AuthorizationServerFactory;
use Gems\Rest\Auth\ClientRepository;
use Gems\Rest\Auth\ScopeRepository;
use Interop\Container\ContainerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use PHPUnit\Framework\TestCase;

class AuthorizationServerFactoryTest extends TestCase
{
    public function testInvokeAuthorizationServerFactory()
    {

        $privateCert = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'private.key');
        $publicCert = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'public.key');
        $appKey = '5yR3GIP65rzT7zxFG7+yVlkJAPKXkPwVNgKE8jY/w50='; //base64_encode(random_bytes(32));

        $config = [
            'oauth2' => [
                'grants' => [
                    'authorization_code' => [
                        'class' => AuthCodeGrant::class,
                        'code_valid' => 'PT10M', // Time an auth code can be exchanged for a token
                        'token_valid' => 'PT1H', // Time a token is valid
                    ],
                    'client_credentials' => [
                        'class' => ClientCredentialsGrant::class,
                        'token_valid' => 'PT1H', // Time a token is valid
                    ],
                    'password' => [
                        'class' => PasswordGrant::class,
                        'token_valid' => 'PT1H', // Time a token is valid
                    ],
                    'refresh_token' => [
                        'class' => RefreshTokenGrant::class,
                        'token_valid' => 'PT1H', // Time a token is valid
                    ],
                ]
            ],
            'certificates' => [
                'private' => $privateCert,
                'public' => $publicCert
            ],
            'app_key' => $appKey,
        ];

        $accessTokenRepository = $this->prophesize(AccessTokenRepository::class)->reveal();
        $clientRepository = $this->prophesize(ClientRepository::class)->reveal();
        $scopeRepository = $this->prophesize(ScopeRepository::class)->reveal();

        $authCodeGrant = $this->prophesize(AuthCodeGrant::class)->reveal();
        $clientCredentialsGrant = $this->prophesize(ClientCredentialsGrant::class)->reveal();
        $passwordGrant = $this->prophesize(PasswordGrant::class)->reveal();
        $refreshTokenGrant = $this->prophesize(RefreshTokenGrant::class)->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);
        $containerProphecy->get(AccessTokenRepository::class)->willReturn($accessTokenRepository);
        $containerProphecy->get(ClientRepository::class)->willReturn($clientRepository);
        $containerProphecy->get(ScopeRepository::class)->willReturn($scopeRepository);

        $containerProphecy->get(AuthCodeGrant::class)->willReturn($authCodeGrant);
        $containerProphecy->get(ClientCredentialsGrant::class)->willReturn($clientCredentialsGrant);
        $containerProphecy->get(PasswordGrant::class)->willReturn($passwordGrant);
        $containerProphecy->get(RefreshTokenGrant::class)->willReturn($refreshTokenGrant);

        $container = $containerProphecy->reveal();

        $authorizationServerFactory = new AuthorizationServerFactory();
        $authorizationServer = $authorizationServerFactory->__invoke($container, 'test');

        $this->assertInstanceOf(AuthorizationServer::class, $authorizationServer);


        unset($config['oauth2']['grants']['authorization_code']['token_valid']);
        unset($config['oauth2']['grants']['client_credentials']['token_valid']);
        unset($config['oauth2']['grants']['password']['token_valid']);
        unset($config['oauth2']['grants']['refresh_token']['token_valid']);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);
        $containerProphecy->get(AccessTokenRepository::class)->willReturn($accessTokenRepository);
        $containerProphecy->get(ClientRepository::class)->willReturn($clientRepository);
        $containerProphecy->get(ScopeRepository::class)->willReturn($scopeRepository);

        $containerProphecy->get(AuthCodeGrant::class)->willReturn($authCodeGrant);
        $containerProphecy->get(ClientCredentialsGrant::class)->willReturn($clientCredentialsGrant);
        $containerProphecy->get(PasswordGrant::class)->willReturn($passwordGrant);
        $containerProphecy->get(RefreshTokenGrant::class)->willReturn($refreshTokenGrant);

        $container = $containerProphecy->reveal();

        $authorizationServerFactory = new AuthorizationServerFactory();
        $authorizationServer = $authorizationServerFactory->__invoke($container, 'test');

        $this->assertInstanceOf(AuthorizationServer::class, $authorizationServer);
    }
}