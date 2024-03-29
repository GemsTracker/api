<?php

use Mezzio\Application;
use Mezzio\Container;
use Mezzio\Delegate;
use Mezzio\Helper;
use Mezzio\Middleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Gems\Rest\Error\ErrorLogEventListenerDelegatorFactory;

return [
    // Provides application-wide services.
    // We recommend using fully-qualified class names whenever possible as
    // service names.
    'dependencies' => [
        // Use 'aliases' to alias a service name to another service. The
        // key is the alias name, the value is the service to which it points.
        'aliases' => [
            'Mezzio\Delegate\DefaultDelegate' => Delegate\NotFoundDelegate::class,
        ],
        // Use 'invokables' for constructor-less services, or services that do
        // not require arguments to the constructor. Map a service name to the
        // class name.
        'invokables' => [
            // Fully\Qualified\InterfaceName::class => Fully\Qualified\ClassName::class,
            Helper\ServerUrlHelper::class => Helper\ServerUrlHelper::class,
        ],
        // Use 'factories' for services provided by callbacks/factory classes.
        'factories'  => [
            Application::class                => Container\ApplicationFactory::class,
            Delegate\NotFoundDelegate::class  => Container\NotFoundDelegateFactory::class,
            Helper\ServerUrlMiddleware::class => Helper\ServerUrlMiddlewareFactory::class,
            Helper\UrlHelper::class           => Helper\UrlHelperFactory::class,
            Helper\UrlHelperMiddleware::class => Helper\UrlHelperMiddlewareFactory::class,

            Laminas\Stratigility\Middleware\ErrorHandler::class => Container\ErrorHandlerFactory::class,
            //Middleware\ErrorResponseGenerator::class         => Container\ErrorResponseGeneratorFactory::class,
            Middleware\ErrorResponseGenerator::class         => \Gems\Rest\Error\JsonErrorResponseGeneratorFactory::class,
            Middleware\NotFoundHandler::class                => Container\NotFoundHandlerFactory::class,

            Blast\BaseUrl\BaseUrlMiddleware::class           => Blast\BaseUrl\BaseUrlMiddlewareFactory::class,
        ],
        'abstract_factories' => [
            \Gems\Rest\Log\PsrLoggerAbstractServiceFactory::class,
        ],
        'delegators' => [
            ErrorHandler::class => [
                ErrorLogEventListenerDelegatorFactory::class,
            ],
        ],
    ],
];
