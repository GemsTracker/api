<?php

return [
    'dependencies' => [
        'factories' => [
            Loader::class => App\Action\LegacyFactory::class,
            ProjectSettings::class => App\Action\LegacyFactory::class,
            Util::class => App\Action\LegacyFactory::class,
            Locale::class => App\Action\LegacyFactory::class,
            Translate::class => App\Action\LegacyFactory::class,
            Cache::class => App\Action\LegacyFactory::class,
        ],
        'aliases' => [
            'Legacyloader' => Loader::class,
            'Legacyproject' => ProjectSettings::class,
            'Legacyutil' => Util::class,
            'Legacylocale' => Locale::class,
            'Legacytranslate' => Translate::class,
            'Legacycache' => Cache::class,
        ],
    ],
];