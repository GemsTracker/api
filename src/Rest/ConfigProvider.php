<?php

namespace Gems\Rest;

use Gems\Rest\Action\ModelRestController;

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
                Action\ModelRestController::class => Action\RestControllerFactory::class,
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
            'respondents' => [
                'model' => 'Model_RespondentModel',
                'methods' => ['GET'],
            ],
            'logs' => [
                'model' => 'Model\\LogModel',
                'methods' => ['GET'],
            ],
        ];

        $routes = [];

        foreach($restModels as $endpoint=>$settings) {

            $methods = array_flip($settings['methods']);
            if (!empty($methods)) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.structure',
                    'path' => '/' . $endpoint . '/structure',
                    'middleware' => [
                        ModelRestController::class
                    ],
                    'options' => [
                        'model' => $settings['model']
                    ],
                    'allowed_methods' => ['GET']
                ];
            }

            if (isset($methods['GET'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.get',
                    'path' => '/' . $endpoint . '[/{id:\d+}]',
                    'middleware' => [
                        ModelRestController::class
                    ],
                    'options' => [
                        'model' => $settings['model']
                    ],
                    'allowed_methods' => ['GET']
                ];
            }

            if (isset($methods['POST'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.post',
                    'path' => '/' . $endpoint,
                    'middleware' => [
                        ModelRestController::class
                    ],
                    'options' => [
                        'model' => $settings['model']
                    ],
                    'allowed_methods' => ['POST']
                ];
            }

            if (isset($methods['PATCH'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.patch',
                    'path' => '/' . $endpoint . '/[{id:\d+}]',
                    'middleware' => [
                        ModelRestController::class
                    ],
                    'options' => [
                        'model' => $settings['model']
                    ],
                    'allowed_methods' => ['PATCH']
                ];
            }

            if (isset($methods['DELETE'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.delete',
                    'path' => '/' . $endpoint . '/[{id:\d+}]',
                    'middleware' => [
                        ModelRestController::class
                    ],
                    'options' => [
                        'model' => $settings['model']
                    ],
                    'allowed_methods' => ['DELETE']
                ];
            }
        }

        return $routes;
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
