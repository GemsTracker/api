<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\AuthCodeGrantFactory;
use Interop\Container\ContainerInterface;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class AuthCodeGrantFactoryTest extends TestCase
{
    public function testInvokeAuthCodeGrantFactory()
    {
        $authCodeRepository = $this->prophesize(AuthCodeRepositoryInterface::class)->reveal();
        $refreshTokenRepository = $this->prophesize(RefreshTokenRepositoryInterface::class)->reveal();

        $config = [
            'oauth2' => [
                'grants' => [
                    'authorization_code' => [
                        'code_valid' => 'PT10M'
                    ]
                ]
            ]

        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);
        $containerProphecy->get(AuthCodeRepositoryInterface::class)->willReturn($authCodeRepository);
        $containerProphecy->get(RefreshTokenRepositoryInterface::class)->willReturn($refreshTokenRepository);
        $container = $containerProphecy->reveal();

        $authCodeGrantFactory = new AuthCodeGrantFactory();
        $authCodeGrant = $authCodeGrantFactory->__invoke($container, 'test');

        $this->assertInstanceOf(AuthCodeGrant::class, $authCodeGrant);
    }
}