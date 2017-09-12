<?php

return [
    'dependencies' => [
        'factories' => [
            Gems\Legacy\LegacyControllerMiddleware::class => \Gems\Rest\Factory\ReflectionFactory::class,
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
        'gems' => GEMS_LIBRARY_DIR . '/controllers',
    ],
];