<?php

use Gems\Rest\Legacy\LegacyFactory;

use Gems_Project_ProjectSettings as ProjectSettings;
use Gems_Util as Util;
use Gems_Util_BasePath as Util_BasePath;
use Zend_Cache as Cache;
use Zend_Translate as Translate;

return [
    'dependencies' => [
        'factories' => [
            Gems_Loader::class => LegacyFactory::class,
            Gems_Project_ProjectSettings::class => LegacyFactory::class,
            Gems_Util::class => LegacyFactory::class,
            Gems_Util_BasePath::class => LegacyFactory::class,
            Gems_AccessLog::class => LegacyFactory::class,
            Zend_Acl::class => LegacyFactory::class,
            Zend_Locale::class => LegacyFactory::class,
            Zend_Session_Namespace::class => LegacyFactory::class,
            'LegacyStaticSession' => LegacyFactory::class,
            Zend_Translate_Adapter::class => LegacyFactory::class,
            Translate::class => LegacyFactory::class,
            Zend_Log::class => LegacyFactory::class,
            Zend_Cache::class => LegacyFactory::class,
            Zend_View::class => LegacyFactory::class,
        ],
        'aliases' => [
            'LegacyLoader' => Gems_Loader::class,
            'LegacyProject' => Gems_Project_ProjectSettings::class,
            'LegacyUtil' => Gems_Util::class,
            'LegacyAccessLog' => Gems_AccessLog::class,
            'LegacyAcl' => Zend_Acl::class,
            'LegacyBasepath' => Gems_Util_BasePath::class,
            'LegacyLocale' => Zend_Locale::class,
            'LegacyLogger' => Zend_Log::class,
            'LegacySession' => Zend_Session_Namespace::class,
            'LegacyTranslate' => Translate::class,
            'LegacyTranslateAdapter' => Zend_Translate_Adapter::class,
            'LegacyCache' => Zend_Cache::class,
            'LegacyView' => Zend_View::class,
        ],
    ],
];