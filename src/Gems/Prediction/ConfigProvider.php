<?php


namespace Gems\Prediction;


use Dev\Action\ChartsDefinitionsAction;
use Gems\Prediction\Action\InputMapping\SurveyQuestions;
use Gems\Prediction\Action\InputMapping\RespondentAction;
use Gems\Prediction\Action\InputMapping\TrackFieldAction;
use Gems\Prediction\Communication\R\PlumberClient;
use Gems\Prediction\Model\DataCollectionRepository;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\RestModelConfigProviderAbstract;
use Gems\Rest\Factory\ReflectionFactory;

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
                RespondentAction::class => ReflectionFactory::class,
                TrackFieldAction::class => ReflectionFactory::class,
                SurveyQuestions::class => ReflectionFactory::class,
            ]
        ];
    }

    public function getTemplates()
    {
        return [];
    }

    protected function getRestModels()
    {
        return [
            'tracks' => [
                'model' => 'Tracker_Model_TrackModel',
                'methods' => ['GET'],
                'hasMany' => ['rounds' => 'rounds'],
            ],
            'rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
            ]
        ];
    }

    public function getRoutes()
    {
        $modelRoutes = parent::getRoutes();

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
            [
                'name' => 'api.input-mapping.respondents',
                'path' => '/input-mapping/respondents',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    RespondentAction::class,
                ],
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.input-mapping.track-field',
                'path' => '/input-mapping/track-field/{trackId:\d+}',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    TrackFieldAction::class,
                ],
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.input-mapping.survey-questions',
                'path' => '/input-mapping/survey-questions/{surveyId:\d+}',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    SurveyQuestions::class,
                ],
                'allowed_methods' => ['GET'],
            ],
        ];

        return array_merge($routes, $modelRoutes);
    }
}