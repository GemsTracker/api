<?php

return [
    'dependencies' => [
        'factories' => [
            \Gems\Rest\Legacy\CurrentUserRepository::class => \Gems\Rest\Factory\ReflectionFactory::class,
        ]
    ],
    'api' => [
        'info' => [
            'name' => 'Gemstracker API',
            'description' => 'REST API for data in Gemstracker',
            'version' => 0.5,
        ],
        'config_providers' => [
            Gems\Rest\ConfigProvider::class,
            Prediction\ConfigProvider::class,
            Pulse\Api\ConfigProvider::class,
        ]
    ],
];