<?php


namespace Gemstest\Rest\Legacy;


use Gems\Rest\Legacy\LegacyFactory;
use Interop\Container\ContainerInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Gems\Rest\Legacy\LegacyCacheFactoryWrapper;
use Zalt\Loader\ProjectOverloader;

class LegacyFactoryTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $fileSystem;

    public function setUp()
    {
        if(!isset($_SESSION)) {
            @session_start();
        }
        $this->fileSystem = vfsStream::setup('testDirectory');

        defined('GEMS_ROOT_DIR') || define('GEMS_ROOT_DIR', vfsStream::url('testDirectory'));
        defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', vfsStream::url('localeDirectory'));
        defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR);
        defined('GEMS_PROJECT_NAME') || define('GEMS_PROJECT_NAME', 'TEST');

        parent::__construct();
    }

    public function testNotFound()
    {
        $container = $this->getContainer();
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, 'nothing');
        $this->assertNull($result);
    }

    public function testGetAccessLog()
    {
        $accessLog = $this->prophesize(\Gems_AccessLog::class)->reveal();

        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();
        $db = $this->prophesize(\Zend_Db_Adapter_Abstract::class)->reveal();
        $loader = $this->prophesize(\Gems_Loader::class)->reveal();

        $container = $this->getContainer(['AccessLog' => $accessLog], ['LegacyCache' => $cache, 'LegacyDb' => $db, 'LegacyLoader' => $loader]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Gems_AccessLog::class);
        $this->assertInstanceOf(\Gems_AccessLog::class, $result);
    }

    public function testGetAcl()
    {
        $zendAcl = $this->prophesize(\Zend_Acl::class)->reveal();
        $acl = $this->prophesize(\Gems_Roles::class);
        $acl->getAcl()->willReturn($zendAcl);
        $acl->reveal();
        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();
        $container = $this->getContainer(['Roles' => $acl], ['LegacyCache' => $cache]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_Acl::class);
        $this->assertInstanceOf(\Zend_Acl::class, $result);
    }

    public function testGetCache()
    {
        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();

        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getCache()->willReturn('File');

        $cacheFactoryWrapper = $this->prophesize(LegacyCacheFactoryWrapper::class);
        $cacheFactoryWrapper->factory(Argument::cetera())->willReturn($cache);

        $this->assertFalse($this->fileSystem->hasChild(GEMS_ROOT_DIR . '/data/cache/'));

        $container = $this->getContainer([\Zend_Cache::class => $cache], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $legacyFactory->setCacheFactoryWrapper($cacheFactoryWrapper->reveal());
        $result = $legacyFactory->__invoke($container, \Zend_Cache::class);

        $this->assertInstanceOf(\Zend_Cache_Core::class, $result);

        $this->assertEquals($cache, \Zend_Db_Table_Abstract::getDefaultMetadataCache(), 'Cache not correctly set in \Zend_Db_Table_Abstract');
        $this->assertEquals($cache, \Zend_Translate::getCache(), 'Cache not correctly set in \Zend_Translate');
        $this->assertEquals($cache, \Zend_Locale::getCache(), 'Cache not correctly set in \Zend_Locale');

        \Zend_Db_Table_Abstract::setDefaultMetadataCache(null);
        \Zend_Translate::removeCache();
        \Zend_Locale::removeCache();

        $this->assertDirectoryExists($this->fileSystem->url() . '/data/cache/');
    }

    public function testGetCacheNoCache()
    {
        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();

        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getCache()->willReturn('none');

        $container = $this->getContainer([\Zend_Cache::class => $cache], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_Cache::class);

        $this->assertInstanceOf(\Zend_Cache_Core::class, $result);
        $this->assertInstanceOf(\Zend_Cache_Core::class, \Zend_Db_Table_Abstract::getDefaultMetadataCache(), 'Cache not correctly set in \Zend_Db_Table_Abstract');
        $this->assertInstanceOf(\Zend_Cache_Core::class, \Zend_Translate::getCache(), 'Cache not correctly set in \Zend_Translate');
        $this->assertInstanceOf(\Zend_Cache_Core::class, \Zend_Locale::getCache(), 'Cache not correctly set in \Zend_Locale');
    }

    public function testGetCacheExistingDirectory()
    {
        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();

        @mkdir(GEMS_ROOT_DIR . "/var/cache/", 0777, true);
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getCache()->willReturn('File');

        $cacheFactoryWrapper = $this->prophesize(LegacyCacheFactoryWrapper::class);
        $cacheFactoryWrapper->factory(Argument::cetera())->willReturn($cache);

        $container = $this->getContainer([\Zend_Cache::class => $cache], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $legacyFactory->setCacheFactoryWrapper($cacheFactoryWrapper->reveal());
        $result = $legacyFactory->__invoke($container, \Zend_Cache::class);

        $this->assertInstanceOf(\Zend_Cache_Core::class, $result);

        \Zend_Db_Table_Abstract::setDefaultMetadataCache(null);
        \Zend_Translate::removeCache();
        \Zend_Locale::removeCache();

        $this->assertDirectoryExists($this->fileSystem->url() . '/var/cache/');
    }

    public function testGetCacheConsoleApc()
    {
        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();

        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getCache()->willReturn('apc');

        $cacheFactoryWrapper = $this->prophesize(LegacyCacheFactoryWrapper::class);
        $cacheFactoryWrapper->factory(Argument::cetera())->willReturn($cache);

        $container = $this->getContainer([\Zend_Cache::class => $cache], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $legacyFactory->setCacheFactoryWrapper($cacheFactoryWrapper->reveal());
        $legacyFactory->testConsoleApc = true;

        $result = $legacyFactory->__invoke($container, \Zend_Cache::class);

        $this->assertInstanceOf(\Zend_Cache_Core::class, $result);
        \Zend_Db_Table_Abstract::setDefaultMetadataCache(null);
        \Zend_Translate::removeCache();
        \Zend_Locale::removeCache();
    }

    public function testGetCacheApc()
    {
        $cache = $this->prophesize(\Zend_Cache_Core::class)->reveal();

        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getCache()->willReturn('apc');

        $cacheFactoryWrapper = $this->prophesize(LegacyCacheFactoryWrapper::class);
        $cacheFactoryWrapper->factory(Argument::cetera())->willReturn($cache);

        $container = $this->getContainer([\Zend_Cache::class => $cache], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $legacyFactory->testApc = true;
        $legacyFactory->setCacheFactoryWrapper($cacheFactoryWrapper->reveal());
        $result = $legacyFactory->__invoke($container, \Zend_Cache::class);

        $this->assertInstanceOf(\Zend_Cache_Core::class, $result);
        \Zend_Db_Table_Abstract::setDefaultMetadataCache(null);
        \Zend_Translate::removeCache();
        \Zend_Locale::removeCache();
    }

    public function testGetLoader()
    {
        $loader = $this->prophesize(\Gems_Loader::class)->reveal();
        $container = $this->getContainer([\Gems_Loader::class => $loader]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Gems_Loader::class);
        $this->assertInstanceOf(\Gems_Loader::class, $result);
    }

    public function testGetLocale()
    {
        $logger = $this->prophesize(\Zend_Locale::class)->reveal();
        $container = $this->getContainer([\Zend_Locale::class => $logger]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_Locale::class);
        $this->assertInstanceOf(\Zend_Locale::class, $result);
    }

    public function testGetLogger()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getLogLevel()->willReturn(\Zend_Log::DEBUG);

        $translateAdapter = $this->prophesize(\Zend_Translate_Adapter::class);
        $translateAdapter->_(Argument::type('string'))->will(function($args) {
           return $args[0];
        });

        $loader = $this->prophesize(\Gems_Log::class)->reveal();
        $container = $this->getContainer([\Gems_Log::class => $loader], ['LegacyProject' => $projectSettings, 'LegacyTranslateAdapter' => $translateAdapter]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Gems_Log::class);
        $this->assertInstanceOf(\Gems_Log::class, $result);

        $this->assertFileExists($this->fileSystem->url() . '/var/logs/errors.log');
    }

    public function testGetLoggerNotAvailable()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getLogLevel()->willReturn(\Zend_Log::DEBUG);

        $translateAdapter = $this->prophesize(\Zend_Translate_Adapter::class);
        $translateAdapter->_(Argument::type('string'))->will(function($args) {
           return $args[0];
        });
        $translateAdapter->translate(Argument::cetera())->will(function($args) {
           return $args[0];
        });


        @mkdir(GEMS_ROOT_DIR . "/var/cache/", 0000, true);

        $loader = $this->prophesize(\Gems_Log::class)->reveal();
        $container = $this->getContainer([\Gems_Log::class => $loader], ['LegacyProject' => $projectSettings, 'LegacyTranslateAdapter' => $translateAdapter]);
        $legacyFactory = new LegacyFactory();
        $this->expectException(\Zend_Exception::class);
        $legacyFactory->__invoke($container, \Gems_Log::class);

    }

    public function testGetProjectSettings()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class)->reveal();
        $container = $this->getContainer(['Project_ProjectSettings' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Gems_Project_ProjectSettings::class);
        $this->assertInstanceOf(\Gems_Project_ProjectSettings::class, $result);
    }

    public function testGetProjectSettingsIni()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class)->reveal();
        $container = $this->getContainer(['Project_ProjectSettings' => $projectSettings], [], ['project' => ['environment' => 'development']]);
        $legacyFactory = new LegacyFactory();

        vfsStream::newFile('config/project.ini')->at($this->fileSystem)->setContent("
[production]
name = Gemstracker
description = 'Gemstracker Test'

[development : production]
");

        $result = $legacyFactory->__invoke($container, \Gems_Project_ProjectSettings::class);
        $this->assertInstanceOf(\Gems_Project_ProjectSettings::class, $result);
    }

    public function testGetProjectSettingsPhp()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class)->reveal();
        $container = $this->getContainer(['Project_ProjectSettings' => $projectSettings]);
        $legacyFactory = new LegacyFactory();

        vfsStream::newFile('config/project.inc')->at($this->fileSystem)->setContent("
<?php
return [
    'name' => 'Gemstracker',
    'description' => 'Gemstracker Test',
];
");

        $result = $legacyFactory->__invoke($container, \Gems_Project_ProjectSettings::class);
        $this->assertInstanceOf(\Gems_Project_ProjectSettings::class, $result);
    }

    public function testGetProjectSettingsTxt()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class)->reveal();
        $container = $this->getContainer(['Project_ProjectSettings' => $projectSettings]);
        $legacyFactory = new LegacyFactory();

        vfsStream::newFile('config/project.txt')->at($this->fileSystem)->setContent("
[production]
name = Gemstracker
description = 'Gemstracker Test'

[development : production]
");

        $result = $legacyFactory->__invoke($container, \Gems_Project_ProjectSettings::class);
        $this->assertInstanceOf(\Gems_Project_ProjectSettings::class, $result);
    }

    public function testGetProjectSettingsFilesXml()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class)->reveal();
        $container = $this->getContainer(['Project_ProjectSettings' => $projectSettings]);
        $legacyFactory = new LegacyFactory();

        vfsStream::newFile('config/project.xml')->at($this->fileSystem)->setContent('
<?xml version="1.0"?>
<configdata>
    <production>
        <name>Gemstracker</name>
        <description>Gemstracker Test</description>
    </production>
    <development extends="production">
    </development>
</configdata>
');

        $result = $legacyFactory->__invoke($container, \Gems_Project_ProjectSettings::class);
        $this->assertInstanceOf(\Gems_Project_ProjectSettings::class, $result);
    }

    public function testGetSession()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getSessionTimeOut()->willReturn(3600);

        \Zend_Session::$_unitTestEnabled = true;

        $session = $this->prophesize(\Zend_Session_Namespace::class)->reveal();
        $container = $this->getContainer([\Zend_Session_Namespace::class => $session], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_Session_Namespace::class);
        $this->assertInstanceOf(\Zend_Session_Namespace::class, $result);
    }

    public function testGetStaticSession()
    {
        \Zend_Session::$_unitTestEnabled = true;

        $container = $this->getContainer();
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, 'LegacyStaticSession');
        $this->assertInstanceOf(\Zend_Session_Namespace::class, $result);
    }

    public function testGetTranslate()
    {
        $locale = $this->prophesize(\Zend_Locale::class)->reveal();

        $container = $this->getContainer([], ['LegacyLocale' => $locale]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_Translate::class);
        $this->assertInstanceOf(\Zend_Translate::class, $result);
    }

    public function testGetTranslateAdapter()
    {
        $translateAdapter = $this->prophesize(\Zend_Translate_Adapter::class)->reveal();
        $translate = $this->prophesize(\Zend_Translate::class);
        $translate->getAdapter()->willReturn($translateAdapter);

        $container = $this->getContainer([], [\Zend_Translate::class => $translate]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_Translate_Adapter::class);
        $this->assertInstanceOf(\Zend_Translate_Adapter::class, $result);
    }

    public function testGetView()
    {
        $projectSettings = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectSettings->getName()->willReturn('TestName');
        $projectSettings->getMetaHeaders()->willReturn(['Content-Type' => "text/html;charset=UTF-8"]);

        $container = $this->getContainer([], ['LegacyProject' => $projectSettings]);
        $legacyFactory = new LegacyFactory();
        $result = $legacyFactory->__invoke($container, \Zend_View::class);
        $this->assertInstanceOf(\Zend_View::class, $result);

        $this->assertEquals('UTF-8', $result->getEncoding());
        $this->assertArrayHasKey('Gems_View_Helper_', $result->getHelperPaths());
        $this->assertArrayHasKey('MUtil_View_Helper_', $result->getHelperPaths());
        $this->assertEquals(\Zend_View_Helper_Doctype::HTML5, $result->doctype()->getDoctype());
    }

    private function getContainer($loaderClasses = [], $containerClasses = [], $config = [])
    {
        $loader = $this->prophesize(ProjectOverloader::class);
        foreach($loaderClasses as $requestedName=>$instance) {
            $loader->create(
                $requestedName,
                Argument::cetera()
            )->willReturn($instance);
        }
        $loader->getOverloaders()->willReturn([
            'Zalt' => 'Zalt',
            'Zend' => 'Zend',
        ]);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('loader')->willReturn($loader->reveal());
        $container->get('config')->willReturn($config);

        foreach($containerClasses as $requestedName=>$instance) {
            $container->get($requestedName)->willReturn($instance);
        }


        return $container->reveal();
    }
}