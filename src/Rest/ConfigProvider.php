<?php

namespace Gems\Rest;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
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
            'templates'    => $this->getTemplates(),
            'routes'       => $this->getRoutes(),
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'invokables' => [
                Action\PingAction::class => Action\PingAction::class,
            ],
            'factories'  => [
                Action\HomePageAction::class => Action\HomePageFactory::class,
                Action\TestModelAction::class => Factory\ReflectionFactory::class,
                Action\OrganizationController::class => Action\RestControllerFactory::class,
            ],
        ];
    }

    public function getModelRoutes()
    {
        $restModels = [
            'organizations' => [
                'model' => 'Model_OrganizationModel',
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
            ],
        ];

        $routes = [];

        foreach($restModels as $endpoint=>$settings) {
            $route = [
                'name' => 'api.'.$endpoint.'.get',
                'path' => '/'.$endpoint.'[/{id:\d+}]',
                'middleware' => [
                    Gems\Rest\Action\ModelRestController::class
                ],
                'allowed_methods' => ['GET']
            ];
            $routes[] = $route;

            $route = [
                'name' => 'api.'.$endpoint.'.post',
                'path' => '/'.$endpoint,
                'middleware' => [
                    Gems\Rest\Action\ModelRestController::class
                ],
                'allowed_methods' => ['POST']
            ];
            $routes[] = $route;



        }


        return [];
    }

    /**
     * Returns the Routes configuration
     * @return array
     */
    public function getRoutes()
    {
        return $this->getModelRoutes();
    }

    /**
     * Returns the templates configuration
     *
     * @return array
     */
    public function getTemplates()
    {
        return [
            'paths' => [
                'app'    => ['templates/app'],
                'error'  => ['templates/error'],
                'layout' => ['templates/layout'],
            ],
        ];
    }
}
