<?php

use Gems\Rest\Legacy\LegacyFactory;

use Gems_Loader as Loader;
use Gems_Project_ProjectSettings as ProjectSettings;
use Gems_Util as Util;
use Gems_Util_BasePath as Util_BasePath;
use Zend_Cache as Cache;
use Zend_Locale as Locale;
use Zend_Translate as Translate;

return [
    'dependencies' => [
        'factories' => [
            Loader::class => LegacyFactory::class,
            ProjectSettings::class => LegacyFactory::class,
            Util::class => LegacyFactory::class,
            Util_BasePath::class => LegacyFactory::class,
            Locale::class => LegacyFactory::class,
            Translate::class => LegacyFactory::class,
            Cache::class => LegacyFactory::class,
        ],
        'aliases' => [
            'Legacyloader' => Loader::class,
            'Legacyproject' => ProjectSettings::class,
            'Legacyutil' => Util::class,
            'Legacybasepath' => Util_BasePath::class,
            'Legacylocale' => Locale::class,
            'Legacytranslate' => Translate::class,
            'Legacycache' => Cache::class,
        ],
    ],
];