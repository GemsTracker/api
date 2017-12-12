<?php


namespace Gemstest\Rest;


use Gems\Rest\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testGetConfig()
    {
        $configProvider = new ConfigProvider();
        $config = $configProvider->__invoke();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
    }
}