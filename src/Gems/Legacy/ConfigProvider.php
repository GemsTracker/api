<?php


namespace Gems\Legacy;


use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;

class ConfigProvider extends RestModelConfigProviderAbstract
{
    protected function getRestModels()
    {
        return [];
    }

    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories' => [
                LegacyControllerMiddleware::class => ReflectionFactory::class,
            ],
        ];
    }

    /**
     * Returns the Routes configuration
     * @return array
     */
    public function getRoutes()
    {
        $modelRoutes = parent::getRoutes();

        $routes = [
            [
                'name' => 'organization-controller',
                'path' => '/legacy/organization[/{action}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    LegacyControllerMiddleware::class
                ],
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => 'Organization',
                ]
            ],
            [
                'name' => 'ask-controller',
                'path' => '/legacy/ask[/{action}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    LegacyControllerMiddleware::class
                ],
                'allowed_methods' => ['GET', 'POST'],
                'options' => [
                    'controller' => 'Ask',
                ]
            ],
        ];

        return array_merge($routes, $modelRoutes);
    }
}