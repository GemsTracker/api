<?php

use Gems\Rest\Legacy\LegacyFactory;
use Gems\Rest\Factory\ReflectionFactory;
use Gems\Event\EventDispatcher;

return [
    'dependencies' => [
        'factories' => [
            Gems_Loader::class => LegacyFactory::class,
            Gems_Project_ProjectSettings::class => LegacyFactory::class,
            Gems_Tracker::class => LegacyFactory::class,
            Gems_Events::class => LegacyFactory::class,
            Gems_User_UserLoader::class => LegacyFactory::class,
            Gems_Util::class => LegacyFactory::class,
            Gems_Util_BasePath::class => LegacyFactory::class,
            Gems_AccessLog::class => LegacyFactory::class,
            Gems_Agenda::class => LegacyFactory::class,
            EventDispatcher::class => LegacyFactory::class,
            Gems_Model::class => LegacyFactory::class,
            'LegacyCurrentUser' => LegacyFactory::class,
            'LegacySource' => LegacyFactory::class,
            Zend_Acl::class => LegacyFactory::class,
            Zend_Locale::class => LegacyFactory::class,
            Zend_Session_Namespace::class => LegacyFactory::class,
            'LegacyStaticSession' => LegacyFactory::class,
            Zend_Translate_Adapter::class => LegacyFactory::class,
            Zend_Translate::class => LegacyFactory::class,
            Gems_Log::class => LegacyFactory::class,
            Zend_Cache::class => LegacyFactory::class,
            \Psr\Cache\CacheItemPoolInterface::class => LegacyFactory::class,
            Zend_View::class => LegacyFactory::class,
            Gems\Legacy\LegacyControllerMiddleware::class => ReflectionFactory::class,
            \Gems\Rest\Middleware\LegacyRequestMiddleware::class => ReflectionFactory::class,
            \Gems\Rest\Request\MezzioRequestWrapper::class => ReflectionFactory::class,
        ],
        'aliases' => [
            'LegacyLoader' => Gems_Loader::class,
            'LegacyProject' => Gems_Project_ProjectSettings::class,
            'LegacyUtil' => Gems_Util::class,
            'LegacyAccessLog' => Gems_AccessLog::class,
            'LegacyAcl' => Zend_Acl::class,
            'LegacyAgenda' => Gems_Agenda::class,
            'LegacyBasepath' => Gems_Util_BasePath::class,
            'LegacyEvent' => EventDispatcher::class,
            'LegacyLocale' => Zend_Locale::class,
            'LegacyLogger' => Gems_Log::class,
            //'LegacyModel' => Gems_Model::class,
            'LegacySession' => Zend_Session_Namespace::class,
            'LegacyTracker' => Gems_Tracker::class,
            'LegacyEvents' => Gems_Events::class,
            'LegacyTranslate' => Zend_Translate::class,
            'LegacyTranslateAdapter' => Zend_Translate_Adapter::class,
            'LegacyUserLoader' => Gems_User_UserLoader::class,
            'LegacyCache' => Zend_Cache::class,
            'LegacyView' => Zend_View::class,
            'LegacyOverLoader' => \Zalt\Loader\ProjectOverloader::class,
            'LegacyRequest' => \Gems\Rest\Request\MezzioRequestWrapper::class,
        ],
    ],
    'routes' => [
        [
            'name' => 'contact',
            'path' => '/contact',
            'middleware' => [
                Gems\Legacy\LegacyControllerMiddleware::class
            ],
            'options' => [
                'controller' => 'contact',
                'action' => 'index',
                'permission' => 'pr.all',
            ],
        ]
    ],
    'controllerDirs' => [
        //'gems' => GEMS_LIBRARY_DIR . '/controllers',
    ],
];
