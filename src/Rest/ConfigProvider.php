<?php

namespace Gems\Rest;

use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;

use Gems\Rest\Legacy\CurrentUserRepository;
use Gems\Rest\Factory\ReflectionFactory;

use Gems\Rest\Auth\AuthorizationServerFactory;
use Gems\Rest\Auth\ResourceServerFactory;


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
                // Default test 
                Action\HomePageAction::class => Action\HomePageFactory::class,
                Action\TestModelAction::class => Factory\ReflectionFactory::class,
                
                // Model Rest 
                Action\ModelRestController::class => Factory\ReflectionFactory::class,
                
                // Current User
                CurrentUserRepository::class => ReflectionFactory::class,

                // Oauth2

                // OAUTH2 servers
                League\OAuth2\Server\AuthorizationServer::class => AuthorizationServerFactory::class,
                League\OAuth2\Server\ResourceServer::class => ResourceServerFactory::class,

                // Middleware
                AuthorizeGemsAndOauthMiddleware::class => ReflectionFactory::class,

                // Actions
                Rest\Auth\AuthorizeAction::class => ReflectionFactory::class,
                Rest\Auth\AccessTokenAction::class => ReflectionFactory::class,

                // Entity repositories
                Gems\Rest\Auth\AccessTokenRepository::class => ReflectionFactory::class,
                Gems\Rest\Auth\AuthCodeRepository::class => ReflectionFactory::class,
                Gems\Rest\Auth\ClientRepository::class => ReflectionFactory::class,
                Gems\Rest\Auth\RefreshTokenRepository::class => ReflectionFactory::class,
                Gems\Rest\Auth\ScopeRepository::class => ReflectionFactory::class,
                Gems\Rest\Auth\UserRepository::class => ReflectionFactory::class,

                League\OAuth3\Server\Repositories\AccessTokenRepositoryInterface::class => ReflectionFactory::class,
                League\OAuth3\Server\Repositories\AuthCodeRepositoryInterface::class => ReflectionFactory::class,
                League\OAuth3\Server\Repositories\ClientRepositoryInterface::class => ReflectionFactory::class,
                League\OAuth3\Server\Repositories\RefreshTokenRepositoryInterface::class => ReflectionFactory::class,
                League\OAuth3\Server\Repositories\ScopeRepositoryInterface::class => ReflectionFactory::class,
                League\OAuth3\Server\Repositories\UserRepositoryInterface::class => ReflectionFactory::class,

                // Grants
                League\OAuth3\Server\Grant\AuthCodeGrant::class => Gems\Rest\Auth\AuthCodeGrantFactory::class,
                League\OAuth3\Server\Grant\ClientCredentialsGrant::class => ReflectionFactory::class,
                League\OAuth3\Server\Grant\ImplicitGrant::class => Gems\Rest\Auth\ImplicitGrantFactory::class,
                League\OAuth3\Server\Grant\PasswordGrant::class => ReflectionFactory::class,
                League\OAuth3\Server\Grant\RefreshTokenGrant::class => ReflectionFactory::class,
            ],
            'aliases' => [
                League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface::class => Gems\Rest\Auth\AccessTokenRepository::class,
                League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface::class => Gems\Rest\Auth\AuthCodeRepository::class,
                League\OAuth2\Server\Repositories\ClientRepositoryInterface::class => Gems\Rest\Auth\ClientRepository::class,
                League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface::class => Gems\Rest\Auth\RefreshTokenRepository::class,
                League\OAuth2\Server\Repositories\ScopeRepositoryInterface::class => Gems\Rest\Auth\ScopeRepository::class,
                League\OAuth2\Server\Repositories\UserRepositoryInterface::class => Gems\Rest\Auth\UserRepository::class,
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
                    'middleware' => $this->getMiddleware(),
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
                    'middleware' => $this->getMiddleware(),
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
                    'middleware' => $this->getMiddleware(),
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
                    'middleware' => $this->getMiddleware(),
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
                    'middleware' => $this->getMiddleware(),
                    'options' => [
                        'model' => $settings['model']
                    ],
                    'allowed_methods' => ['DELETE']
                ];
            }
        }

        return $routes;
    }

    public function getMiddleware()
    {
        return [
            AuthorizeGemsAndOauthMiddleware::class,
            ModelRestController::class
        ];
    }

    /**
     * Returns the Routes configuration
     * @return array
     */
    public function getRoutes()
    {
        $routes = [
            [
                'name' => 'access_token',
                'path' => '/access_token',
                'middleware' => [
                    Gems\Rest\Auth\MergeUsernameOrganizationMiddleware::class,
                    Rest\Auth\AccessTokenAction::class
                ],
                'allowed_methods' => ['POST'],
            ],
            [
                'name' => 'authorize',
                'path' => '/authorize',
                'middleware' => [
                    Gems\Rest\Auth\MergeUsernameOrganizationMiddleware::class,
                    Rest\Auth\AuthorizeAction::class
                ],
                'allowed_methods' => ['GET', 'POST'],
            ],
        ];

        return array_merge($routes, $this->getModelRoutes());
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
                'oauth'  => ['templates/oauth'],
            ],
        ];
    }
}
