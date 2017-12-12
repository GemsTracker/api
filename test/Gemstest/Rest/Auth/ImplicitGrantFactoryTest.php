<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\ImplicitGrantFactory;
use Interop\Container\ContainerInterface;
use League\OAuth2\Server\Grant\ImplicitGrant;
use PHPUnit\Framework\TestCase;

class ImplicitGrantFactoryTest extends TestCase
{
    public function testInvokeImplicitGrantFactory()
    {
        $config = [
            'oauth2' => [
                'grants' => [
                    'implicit' => [
                        'code_valid' => 'PT10M'
                    ]
                ]
            ]
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);
        $container = $containerProphecy->reveal();

        $implicitGrantFactory = new ImplicitGrantFactory();
        $implicitGrant = $implicitGrantFactory->__invoke($container, 'test123');

        $this->assertInstanceOf(ImplicitGrant::class, $implicitGrant);
    }
}