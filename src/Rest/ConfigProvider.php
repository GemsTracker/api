<?php

namespace Gems\Rest;

use Gems\Rest\Acl\AclFactory;
use Gems\Rest\Acl\AclRepository;
use Gems\Rest\Action\DevAction;
use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Action\PingController;
use Gems\Rest\Auth\AccessTokenAction;
use Gems\Rest\Auth\AccessTokenRepository;
use Gems\Rest\Auth\AuthCodeGrantFactory;
use Gems\Rest\Auth\AuthCodeRepository;
use Gems\Rest\Auth\AuthorizeAction;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;

use Gems\Rest\Auth\ClientRepository;
use Gems\Rest\Auth\CorsMiddleware;
use Gems\Rest\Auth\ImplicitGrantFactory;
use Gems\Rest\Auth\MergeUsernameOrganizationMiddleware;
use Gems\Rest\Auth\RefreshTokenRepository;
use Gems\Rest\Auth\ScopeRepository;
use Gems\Rest\Auth\UserRepository;
use Gems\Rest\Legacy\CurrentUserRepository;
use Gems\Rest\Factory\ReflectionFactory;

use Gems\Rest\Auth\AuthorizationServerFactory;
use Gems\Rest\Auth\ResourceServerFactory;
use Gems\Rest\Middleware\ApiGateMiddleware;
use Gems\Rest\Middleware\ApiOrganizationGateMiddleware;
use Gems\Rest\Middleware\SecurityHeadersMiddleware;
use Gems\Rest\Repository\LoginAttemptsRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Zend\Permissions\Acl\Acl;


/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider extends RestModelConfigProviderAbstract
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
            'certificates' => $this->getCertificates(),
            'oauth2' => $this->getOauth2(),
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getCertificates()
    {
        return [
            //'public' => 'file://' . GEMS_ROOT_DIR . '/var/settings/gems.public.key',
            //'private' => 'file://' . GEMS_ROOT_DIR . '/var/settings/gems.private.key',
            'public' => 'file:///var/www/expressive/var/settings/gems.public.key',
            'private' => 'file:///var/www/expressive/var/settings/gems.private.key',
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

                SecurityHeadersMiddleware::class => Factory\ReflectionFactory::class,

                // Default test 
                Action\HomePageAction::class => Action\HomePageFactory::class,
                Action\TestModelAction::class => Factory\ReflectionFactory::class,
                
                // Model Rest 
                Action\ModelRestController::class => Factory\ReflectionFactory::class,
                
                // Current User
                CurrentUserRepository::class => ReflectionFactory::class,

                // Oauth2

                // OAUTH2 servers
                AuthorizationServer::class => AuthorizationServerFactory::class,
                ResourceServer::class => ResourceServerFactory::class,

                // Middleware
                AuthorizeGemsAndOauthMiddleware::class => ReflectionFactory::class,
                ApiGateMiddleware::class => ReflectionFactory::class,
                ApiOrganizationGateMiddleware::class => ReflectionFactory::class,

                // Actions
                AuthorizeAction::class => ReflectionFactory::class,
                AccessTokenAction::class => ReflectionFactory::class,

                PingController::class => ReflectionFactory::class,
                DevAction::class => ReflectionFactory::class,

                // Entity repositories
                AccessTokenRepository::class => ReflectionFactory::class,
                AuthCodeRepository::class => ReflectionFactory::class,
                ClientRepository::class => ReflectionFactory::class,
                RefreshTokenRepository::class => ReflectionFactory::class,
                ScopeRepository::class => ReflectionFactory::class,
                UserRepository::class => ReflectionFactory::class,

                // Main repositories
                AclRepository::class => ReflectionFactory::class,

                Acl::class => AclFactory::class,
                LoginAttemptsRepository::class => ReflectionFactory::class,

                //AccessTokenRepositoryInterface::class => ReflectionFactory::class,
                //AuthCodeRepositoryInterface::class => ReflectionFactory::class,
                //ClientRepositoryInterface::class => ReflectionFactory::class,
                //RefreshTokenRepositoryInterface::class => ReflectionFactory::class,
                //ScopeRepositoryInterface::class => ReflectionFactory::class,
                //UserRepositoryInterface::class => ReflectionFactory::class,

                // Grants
                AuthCodeGrant::class => AuthCodeGrantFactory::class,
                ClientCredentialsGrant::class => ReflectionFactory::class,
                ImplicitGrant::class => ImplicitGrantFactory::class,
                PasswordGrant::class => ReflectionFactory::class,
                RefreshTokenGrant::class => ReflectionFactory::class,
            ],
            'aliases' => [
                AccessTokenRepositoryInterface::class => AccessTokenRepository::class,
                AuthCodeRepositoryInterface::class => AuthCodeRepository::class,
                ClientRepositoryInterface::class => ClientRepository::class,
                RefreshTokenRepositoryInterface::class => RefreshTokenRepository::class,
                ScopeRepositoryInterface::class => ScopeRepository::class,
                UserRepositoryInterface::class => UserRepository::class,
            ],
        ];
    }

    public function getRestModels()
    {
        return [
            /*'organizations' => [
                'model' => 'Model_OrganizationModel',
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
            ],
            'respondents' => [
                'model' => 'Model_RespondentModel',
                'methods' => ['GET'],
                'applySettings' => 'applyEditSettings',
            ],
            'logs' => [
                'model' => 'Model\\LogModel',
                'methods' => ['GET'],
            ],*/
        ];
    }

    public function getOauth2()
    {
        return [
            'grants' => [
                'authorization_code' => [
                    'class' => AuthCodeGrant::class,
                    'code_valid' => 'PT10M', // Time an auth code can be exchanged for a token
                    'token_valid' => 'PT1H', // Time a token is valid
                ],
                /*'client_credentials' => [
                    'class' => ClientCredentialsGrant::class,
                    'token_valid' => 'PT1H', // Time a token is valid
                ],*/
                'implicit' => [
                    'class' => ImplicitGrant::class,
                    'code_valid' => 'PT1H',
                    'token_valid' => 'PT1H', // Time a token is valid
                ],
                'password' => [
                    'class' => PasswordGrant::class,
                    'token_valid' => 'PT1H', // Time a token is valid
                ],
                'refresh_token' => [
                    'class' => RefreshTokenGrant::class,
                    'token_valid' => 'PT1H', // Time a token is valid
                ],
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
                'name' => 'dev',
                'path' => '/dev',
                'middleware' => [
                    DevAction::class,
                ],
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'access_token',
                'path' => '/access_token',
                'middleware' => [
                    MergeUsernameOrganizationMiddleware::class,
                    AccessTokenAction::class
                ],
                'allowed_methods' => ['POST'],
            ],
            [
                'name' => 'authorize',
                'path' => '/authorize',
                'middleware' => [
                    MergeUsernameOrganizationMiddleware::class,
                    AuthorizeAction::class
                ],
                'allowed_methods' => ['GET', 'POST'],
            ],
            [
                'name' => 'ping',
                'path' => '/ping',
                'middleware' => $this->getCustomActionMiddleware(PingController::class),
                'allowed_methods' => ['GET'],
            ],
        ];

        return array_merge($routes, $modelRoutes);
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
