<?php


namespace GemsTest\Rest\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Action\RestControllerFactory;
use GemsTest\Rest\Action\ArrayModelRestController;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zalt\Loader\ProjectOverloader;
use Zend\Expressive\Helper\UrlHelper;

class RestControllerFactoryTest extends TestCase
{
    public function testInvoke()
    {
        $factory = new RestControllerFactory();

        $containerProphesy  = $this->prophesize(ContainerInterface::class);
        $urlHelper          = $this->prophesize(UrlHelper::class)->reveal();
        $loader             = $this->prophesize(ProjectOverloader::class)->reveal();
        $db                 = $this->prophesize(\Zend_Db_Adapter_Abstract::class)->reveal();

        $containerProphesy->get(UrlHelper::class)->willReturn($urlHelper);
        $containerProphesy->get('loader')->willReturn($loader);
        $containerProphesy->get('LegacyDb')->willReturn($db);

        $container = $containerProphesy->reveal();

        $controller = $factory->__invoke($container, ArrayModelRestController::class, []);

        $this->assertInstanceOf(RestControllerAbstract::class, $controller, 'Created controller not instance of Gems\Rest\Action\RestControllerAbstract');
    }
}