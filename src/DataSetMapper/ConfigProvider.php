<?php


namespace Gems\DataSetMapper;

use Gems\DataSetMapper\Action\CalculatedDataAction;
use Gems\DataSetMapper\Action\DataAction;
use Gems\DataSetMapper\Action\InputMapping\RespondentAction;
use Gems\DataSetMapper\Action\InputMapping\SurveyQuestions;
use Gems\DataSetMapper\Action\InputMapping\TrackFieldAction;
use Gems\DataSetMapper\Model\DataSetMappingModel;
use Gems\DataSetMapper\Model\DataSetModel;
use Gems\DataSetMapper\Model\DataSetModelsWithMappingModel;
use Gems\DataSetMapper\Repository\DataSetRepository;
use Gems\Rest\Factory\ProjectOverloaderFactory;
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
                DataSetRepository::class => ProjectOverloaderFactory::class,

                CalculatedDataAction::class => ReflectionFactory::class,
                DataAction::class => ReflectionFactory::class,

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
            'data-collection/tracks' => [
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
            'data-collection/rounds' => [
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
            'data-collection/data-collection-models' => [
                'model' => DataSetModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'gdc_id',
                    'gdc_collection_id',
                    'gdc_name',
                    'gdc_id_track',
                    'gdc_url',
                ],
            ],
            'data-collection/data-collection-model-mappings' => [
                'model' => DataSetMappingModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'gdcm_data_collection_id',
                    'gdcm_name',
                    'gdcm_required',
                    'gdcm_type',
                    'gdcm_type_id',
                    'gdcm_type_sub_id',
                    'gdcm_custom_mapping',
                ]
            ],
            'data-collection/data-collection-model-with-mapping' => [
                'model' => DataSetModelsWithMappingModel::class,
                'methods' => ['GET', 'PATCH', 'POST'],
                'allowed_fields' => [
                    'gdc_id',
                    'gdc_source_id',
                    'gdc_name',
                    'gdc_id_track',
                    'gdc_url',
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
                'name' => 'api.data-set/calculated-data',
                'path' => '/data-set/calculated-data/[{collectionId:[a-zA-Z0-9-_]+}]',
                'middleware' => $this->getCustomActionMiddleware(CalculatedDataAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.data-set/data',
                'path' => '/data-set/data/[{collectionId:[a-zA-Z0-9-_]+}]',
                'middleware' => $this->getCustomActionMiddleware(DataAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.data-set/respondents',
                'path' => '/data-set/respondents',
                'middleware' => $this->getCustomActionMiddleware(RespondentAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.data-set/track-fields',
                'path' => '/data-set/track-fields/{trackId:\d+}',
                'middleware' => $this->getCustomActionMiddleware(TrackFieldAction::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'api.data-set/survey-questions',
                'path' => '/data-set/survey-questions/{surveyId:\d+}',
                'middleware' => $this->getCustomActionMiddleware(SurveyQuestions::class),
                'allowed_methods' => ['GET'],
            ],
        ];

        return array_merge($routes, $modelRoutes);
    }
}
