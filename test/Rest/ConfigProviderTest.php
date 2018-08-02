<?php


namespace Gemstest\Rest;


use Gems\Rest\ConfigProvider;
use GemsTest\Rest\Action\TestMessageModelRestController;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testGetCustomActionMiddleware()
    {
        $configProvider = $configProvider = new ConfigProvider();
        $middleware = $configProvider->getCustomActionMiddleware(TestMessageModelRestController::class);

        $this->assertTrue(is_array($middleware));
        foreach($middleware as $className) {
            $this->assertTrue(class_exists($className));
        }

        $this->assertTrue(in_array(TestMessageModelRestController::class, $middleware));
    }

    public function testGetMiddleware()
    {
        $configProvider = $configProvider = new ConfigProvider();
        $middleware = $configProvider->getMiddleware();

        $this->assertTrue(is_array($middleware));
        foreach($middleware as $className) {
            $this->assertTrue(class_exists($className));
        }
    }

    public function testGetConfig()
    {
        $configProvider = new ConfigProvider();
        $config = $configProvider->__invoke();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
    }
}