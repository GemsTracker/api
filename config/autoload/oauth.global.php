<?php

use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\Auth\AuthorizationServerFactory;
use Gems\Rest\Auth\ResourceServerFactory;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;

return [
    'certificates' => [
        //'public' => 'file://' . GEMS_ROOT_DIR . '/var/settings/gems.public.key',
        //'private' => 'file://' . GEMS_ROOT_DIR . '/var/settings/gems.private.key',
        'public' => 'file:///var/www/expressive/var/settings/gems.public.key',
        'private' => 'file:///var/www/expressive/var/settings/gems.private.key',
    ],
    'oauth2' => [
        'grants' => [
            'authorization_code' => [
                'class' => League\OAuth2\Server\Grant\AuthCodeGrant::class,
                'code_valid' => 'PT10M', // Time an auth code can be exchanged for a token
                'token_valid' => 'PT1H', // Time a token is valid
            ],
            'client_credentials' => [
                'class' => League\OAuth2\Server\Grant\ClientCredentialsGrant::class,
                'token_valid' => 'PT1H', // Time a token is valid
            ],
            'implicit' => [
                'class' => League\OAuth2\Server\Grant\ImplicitGrant::class,
                'code_valid' => 'PT1H',
                'token_valid' => 'PT1H', // Time a token is valid
            ],
            'password' => [
                'class' => League\OAuth2\Server\Grant\PasswordGrant::class,
                'token_valid' => 'PT1H', // Time a token is valid
            ],
            'refresh_token' => [
                League\OAuth2\Server\Grant\RefreshTokenGrant::class,
                'token_valid' => 'PT1H', // Time a token is valid
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            // OAUTH2 servers
            League\OAuth2\Server\AuthorizationServer::class => AuthorizationServerFactory::class,
            League\OAuth2\Server\ResourceServer::class => ResourceServerFactory::class,

            // Middleware
            ResourceServerMiddleware::class => ReflectionFactory::class,

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

            League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface::class => ReflectionFactory::class,
            League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface::class => ReflectionFactory::class,
            League\OAuth2\Server\Repositories\ClientRepositoryInterface::class => ReflectionFactory::class,
            League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface::class => ReflectionFactory::class,
            League\OAuth2\Server\Repositories\ScopeRepositoryInterface::class => ReflectionFactory::class,
            League\OAuth2\Server\Repositories\UserRepositoryInterface::class => ReflectionFactory::class,

            // Grants
            League\OAuth2\Server\Grant\AuthCodeGrant::class => Gems\Rest\Auth\AuthCodeGrantFactory::class,
            League\OAuth2\Server\Grant\ClientCredentialsGrant::class => ReflectionFactory::class,
            League\OAuth2\Server\Grant\ImplicitGrant::class => Gems\Rest\Auth\ImplicitGrantFactory::class,
            League\OAuth2\Server\Grant\PasswordGrant::class => ReflectionFactory::class,
            League\OAuth2\Server\Grant\RefreshTokenGrant::class => ReflectionFactory::class,
        ],
        'aliases' => [
            League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface::class => Gems\Rest\Auth\AccessTokenRepository::class,
            League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface::class => Gems\Rest\Auth\AuthCodeRepository::class,
            League\OAuth2\Server\Repositories\ClientRepositoryInterface::class => Gems\Rest\Auth\ClientRepository::class,
            League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface::class => Gems\Rest\Auth\RefreshTokenRepository::class,
            League\OAuth2\Server\Repositories\ScopeRepositoryInterface::class => Gems\Rest\Auth\ScopeRepository::class,
            League\OAuth2\Server\Repositories\UserRepositoryInterface::class => Gems\Rest\Auth\UserRepository::class,
        ],
    ],
    'routes' => [
        [
            'name' => 'nyan',
            'path' => '/nyan',
            'middleware' => [
                ResourceServerMiddleware::class,
                Gems\Rest\Action\TestModelAction::class
            ],
            'allowed_methods' => ['GET']
        ],
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
    ],
    'templates' => [
        'paths' => [
            'oauth' => 'templates/oauth'
        ],
    ]
];