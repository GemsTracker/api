<?php


namespace Gems\Legacy;


use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;

class ConfigProvider extends RestModelConfigProviderAbstract
{
    public function getRestModels()
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
    public function getRoutes($includeModelRoutes = true)
    {
        $modelRoutes = parent::getRoutes($includeModelRoutes = true);

        $routes = [
            [
                'name' => 'controller-action',
                'path' => '/legacy/[{controller}[/{action}[/{params}]]]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    LegacyControllerMiddleware::class
                ],
                'allowed_methods' => ['GET', 'POST'],
            ],
        ];

        return array_merge($routes, $modelRoutes);
    }
}