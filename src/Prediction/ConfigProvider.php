<?php


namespace Prediction;


use Dev\Action\ChartDataAction;
use Dev\Action\ChartsDefinitionsAction;
use Prediction\Model\PredictionModelsModel;
use Prediction\Action\InputMapping\RespondentAction;
use Prediction\Action\InputMapping\SurveyQuestions;
use Prediction\Action\InputMapping\TrackFieldAction;
use Prediction\Communication\R\PlumberClient;
use Prediction\Model\DataCollectionRepository;
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
                ChartDataAction::class => ReflectionFactory::class,
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
            /*'tracks' => [
                'model' => 'Tracker_Model_TrackModel',
                'methods' => ['GET'],
                'hasMany' => ['rounds' => 'rounds'],
            ],
            'rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
            ],*/
            'prediction-models' => [
                'model' => PredictionModelsModel::class,
                'methods' => ['GET'],
            ],
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
                'name' => 'api.charts',
                'path' => '/charts/[{modelId:[a-zA-Z0-9-_]+}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    ChartDataAction::class,
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