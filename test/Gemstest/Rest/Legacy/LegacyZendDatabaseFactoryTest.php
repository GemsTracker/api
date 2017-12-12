<?php


namespace Gemstest\Rest\Legacy;


use Gems\Rest\Legacy\LegacyZendDatabaseFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class LegacyZendDatabaseFactoryTest extends TestCase
{
    public function testInvokeLegacyZendDatabaseFactory()
    {
        $legacyZendDatabaseFactory = new LegacyZendDatabaseFactory();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(
            [
                'db' => [
                    'adapter' => 'Pdo_sqlite',
                    'dbname' => ':memory:',
                ]
            ]
        );

        $requestedName = 'test';
        $options = [];

        $db = $legacyZendDatabaseFactory->__invoke($container->reveal(), $requestedName, $options);

        $this->assertInstanceOf(\Zend_Db_Adapter_Pdo_Sqlite::class, $db, 'Expected a Zend 1 Db Sqlite adapter');
        $this->assertInstanceOf(\Zend_Db_Adapter_Pdo_Sqlite::class, \Zend_Db_Table::getDefaultAdapter(), 'Zend Db adapter not set as default Adapter');
        $this->assertInstanceOf(\Zend_Db_Adapter_Pdo_Sqlite::class, \Zend_Registry::get('db'), 'Zend Db adapter not set in registry');
    }

    public function testNoDbConfigException()
    {
        $legacyZendDatabaseFactory = new LegacyZendDatabaseFactory();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No database configuration found');
        $legacyZendDatabaseFactory->__invoke($container->reveal(), 'test', []);
    }

    public function testNoAdapterConfigException()
    {
        $legacyZendDatabaseFactory = new LegacyZendDatabaseFactory();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(['db' => []]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No database adapter set in config');
        $legacyZendDatabaseFactory->__invoke($container->reveal(), 'test', []);
    }

    public function testNoDbNameConfigException()
    {
        $legacyZendDatabaseFactory = new LegacyZendDatabaseFactory();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(['db' => ['adapter' => 'mysqli']]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No database set in config');
        $legacyZendDatabaseFactory->__invoke($container->reveal(), 'test', []);
    }

    public function testAlternativeNames()
    {
        $legacyZendDatabaseFactory = new LegacyZendDatabaseFactory();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(
            [
                'db' => [
                    'driver' => 'Pdo_sqlite',
                    'database' => ':memory:',
                ]
            ]
        );

        $requestedName = 'test';
        $options = [];

        $db = $legacyZendDatabaseFactory->__invoke($container->reveal(), $requestedName, $options);

        $this->assertInstanceOf(\Zend_Db_Adapter_Pdo_Sqlite::class, $db, 'Expected a Zend 1 Db Sqlite adapter');
    }
}