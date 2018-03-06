<?php


namespace Gems\Prediction;


use Dev\Action\ChartsDefinitionsAction;
use Gems\Prediction\Communication\R\PlumberClient;
use Gems\Prediction\Model\DataCollectionRepository;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\Factory\ReflectionFactory;

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
            //'templates'    => $this->getTemplates(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories' => [
                DataCollectionRepository::class => ReflectionFactory::class,
                PlumberClient::class => ReflectionFactory::class,
                ChartsDefinitionsAction::class => ReflectionFactory::class,
            ]
        ];
    }

    public function getTemplates()
    {
        return [];
    }

    public function getRoutes()
    {
        $routes = [
            [
                'name' => 'api.charts.definitions',
                'path' => '/charts/definitions',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    ChartsDefinitionsAction::class,
                ],
                'allowed_methods' => ['GET'],
            ],
        ];

        return $routes;

    }
}