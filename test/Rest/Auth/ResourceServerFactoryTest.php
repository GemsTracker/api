<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\AccessTokenRepository;
use Gems\Rest\Auth\ResourceServerFactory;
use Interop\Container\ContainerInterface;
use League\OAuth2\Server\ResourceServer;
use PHPUnit\Framework\TestCase;

class ResourceServerFactoryTest extends TestCase
{
    public function testInvokeResourceServerFactory()
    {
        $accessTokenRepository = $this->prophesize(AccessTokenRepository::class)->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $cert = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'public.key');

        $config = [
            'certificates' => [
                'public' => $cert,
            ]
        ];
        $containerProphecy->get('config')->willReturn($config);
        $containerProphecy->get(AccessTokenRepository::class)->willReturn($accessTokenRepository);
        $container = $containerProphecy->reveal();

        $resourceServerFactory = new ResourceServerFactory();
        $resourceServer = $resourceServerFactory->__invoke($container, 'test');

        $this->assertInstanceOf(ResourceServer::class, $resourceServer);
    }
}