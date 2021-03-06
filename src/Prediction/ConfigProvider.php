<?php


namespace Prediction;


use Prediction\Action\ChartDataAction;
use Prediction\Action\ChartsDefinitionsAction;
use Prediction\Model\PredictionModelsMappingModel;
use Prediction\Model\PredictionModelsModel;
use Prediction\Action\InputMapping\RespondentAction;
use Prediction\Action\InputMapping\SurveyQuestions;
use Prediction\Action\InputMapping\TrackFieldAction;
use Prediction\Communication\R\PlumberClient;
use Prediction\Model\DataCollectionRepository;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\RestModelConfigProviderAbstract;
use Gems\Rest\Factory\ReflectionFactory;
use Prediction\Model\PredictionModelsWithMappingModel;

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

    public function getRestModels()
    {
        return [
            'prediction/tracks' => [
                'model' => 'Tracker_Model_TrackModel',
                'methods' => ['GET'],
                'hasMany' => ['rounds' => 'rounds'],
                'allowed_fields' => [
                    'gtr_id_track',
                    'gtr_track_name',
                ],
                'multiOranizationField' => [
                    'field' => 'gtr_organizations',
                    'separator' => '|',
                ],
                'organizationIdField' => 'gtr_organizations',
            ],
            'prediction/rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
                'allowed_fields' => [
                    'gro_id_round',
                    'gro_id_track',
                    'gro_id_order',
                    'gro_id_survey',
                    'gro_survey_name',
                    'gro_round_description',
                ],
            ],
            'prediction/prediction-models' => [
                'model' => PredictionModelsModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'gpm_id',
                    'gpm_source_id',
                    'gpm_name',
                    'gpm_id_track',
                    'gpm_url',
                ],
            ],
            'prediction/prediction-model-mappings' => [
                'model' => PredictionModelsMappingModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'gpmm_prediction_model_id',
                    'gpmm_name',
                    'gpmm_required',
                    'gpmm_type',
                    'gpmm_type_id',
                    'gpmm_type_sub_id',
                    'gpmm_custom_mapping',
                ]
            ],
            'prediction/prediction-model-with-mappings' => [
                'model' => PredictionModelsWithMappingModel::class,
                'methods' => ['GET', 'PATCH', 'POST'],
                'allowed_fields' => [
                    'gpm_id',
                    'gpm_source_id',
                    'gpm_name',
                    'gpm_id_track',
                    'gpm_url',
                    'mappings',
                ],
            ],
        ];
    }

    public function getRoutes($includeModelRoutes=true)
    {
        $modelRoutes = parent::getRoutes($includeModelRoutes);

        $routes = [
            [
                'name' => 'api.prediction/chart-definitions',
                'path' => '/prediction/chart-definitions',
                'middleware' => $this->getCustomActionMiddleware(ChartsDefinitionsAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.prediction/charts',
                'path' => '/prediction/charts/[{modelId:[a-zA-Z0-9-_]+}]',
                'middleware' => $this->getCustomActionMiddleware(ChartDataAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.prediction/respondents',
                'path' => '/prediction/respondents',
                'middleware' => $this->getCustomActionMiddleware(RespondentAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.prediction/track-fields',
                'path' => '/prediction/track-fields/{trackId:\d+}',
                'middleware' => $this->getCustomActionMiddleware(TrackFieldAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.prediction/survey-questions',
                'path' => '/prediction/survey-questions/{surveyId:\d+}',
                'middleware' => $this->getCustomActionMiddleware(SurveyQuestions::class),
                'allowed_methods' => ['GET'],
            ],
        ];

        return array_merge($routes, $modelRoutes);
    }
}